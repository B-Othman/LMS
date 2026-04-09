<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\QuizAttemptStatus;
use App\Enums\QuizQuestionType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuizService
{
    public function __construct(
        private readonly QuizScoringService $scoring,
    ) {}

    public function findQuiz(int $id): Quiz
    {
        return Quiz::query()
            ->with(['course', 'lesson.module', 'questions.options'])
            ->withCount('questions')
            ->findOrFail($id);
    }

    public function findQuestion(int $id): QuizQuestion
    {
        return QuizQuestion::query()
            ->with(['quiz.course', 'quiz.lesson.module', 'options'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function createQuiz(array $data): Quiz
    {
        return DB::transaction(function () use ($data) {
            [$course, $lesson] = $this->resolveContext(
                isset($data['course_id']) ? (int) $data['course_id'] : null,
                isset($data['lesson_id']) ? (int) $data['lesson_id'] : null,
            );

            if (! $course) {
                throw new \DomainException('Quizzes must belong to a course or lesson.');
            }

            if ($lesson && $lesson->quiz()->exists()) {
                throw new \DomainException('This lesson already has a quiz attached.');
            }

            $quiz = Quiz::query()->create([
                'course_id' => $course->id,
                'lesson_id' => $lesson?->id,
                'tenant_id' => $course->tenant_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'pass_score' => $data['pass_score'] ?? 70,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'attempts_allowed' => $data['attempts_allowed'] ?? 0,
                'shuffle_questions' => $data['shuffle_questions'] ?? false,
                'show_results_to_learner' => $data['show_results_to_learner'] ?? true,
                'status' => $data['status'] ?? 'draft',
            ]);

            return $this->findQuiz($quiz->id);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $quiz->update($data);

        return $this->findQuiz($quiz->id);
    }

    /** @param array<string, mixed> $data */
    public function addQuestion(Quiz $quiz, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($quiz, $data) {
            $type = QuizQuestionType::from((string) $data['question_type']);
            $options = array_values($data['options'] ?? []);

            $this->validateQuestionOptions($type, $options);

            $question = $quiz->questions()->create([
                'question_type' => $type,
                'prompt' => $data['prompt'],
                'explanation' => $data['explanation'] ?? null,
                'points' => $data['points'] ?? 1,
                'sort_order' => $data['sort_order'] ?? ((int) $quiz->questions()->max('sort_order') + 1),
            ]);

            $this->syncQuestionOptions($question, $options);

            return $this->findQuestion($question->id);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($question, $data) {
            $type = array_key_exists('question_type', $data)
                ? QuizQuestionType::from((string) $data['question_type'])
                : $question->question_type;

            $options = array_key_exists('options', $data)
                ? array_values($data['options'] ?? [])
                : $question->options->map(fn (QuestionOption $option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'is_correct' => $option->is_correct,
                    'sort_order' => $option->sort_order,
                ])->values()->all();

            $this->validateQuestionOptions($type, $options);

            $question->update([
                'question_type' => $type,
                'prompt' => $data['prompt'] ?? $question->prompt,
                'explanation' => array_key_exists('explanation', $data) ? $data['explanation'] : $question->explanation,
                'points' => $data['points'] ?? $question->points,
                'sort_order' => $data['sort_order'] ?? $question->sort_order,
            ]);

            if (array_key_exists('options', $data) || $type === QuizQuestionType::ShortAnswer) {
                $this->syncQuestionOptions($question, $options);
            }

            return $this->findQuestion($question->id);
        });
    }

    public function deleteQuestion(QuizQuestion $question): void
    {
        $question->delete();
    }

    /**
     * @param  array<int, array{id: int, sort_order: int}>  $items
     */
    public function reorderQuestions(Quiz $quiz, array $items): void
    {
        DB::transaction(function () use ($quiz, $items) {
            foreach ($items as $item) {
                $quiz->questions()->where('id', $item['id'])->update([
                    'sort_order' => $item['sort_order'],
                ]);
            }
        });
    }

    public function loadAttempt(int $id): QuizAttempt
    {
        return QuizAttempt::query()
            ->with([
                'quiz.course',
                'quiz.lesson.module',
                'quiz.questions.options',
                'answers',
            ])
            ->findOrFail($id);
    }

    public function findAttemptForUser(User $user, int $attemptId): QuizAttempt
    {
        $attempt = QuizAttempt::query()
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $attempt = $this->loadAttempt($attempt->id);

        if ($this->attemptHasExpired($attempt)) {
            return $this->scoring->scoreAttempt($attempt, []);
        }

        return $attempt;
    }

    /** @return Collection<int, QuizAttempt> */
    public function listAttemptsForUser(User $user): Collection
    {
        return QuizAttempt::query()
            ->with(['quiz.course'])
            ->where('user_id', $user->id)
            ->orderByDesc('started_at')
            ->get();
    }

    public function startAttempt(Quiz $quiz, User $user): QuizAttempt
    {
        $quiz->loadMissing('course', 'lesson.module', 'questions.options');
        $this->ensureQuizCanBeAttempted($quiz);

        $enrollment = $this->resolveActiveEnrollment($user, $quiz);

        $existingAttempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_id', $enrollment->id)
            ->where('user_id', $user->id)
            ->where('status', QuizAttemptStatus::InProgress->value)
            ->latest('started_at')
            ->first();

        if ($existingAttempt) {
            $existingAttempt = $this->loadAttempt($existingAttempt->id);

            if (! $this->attemptHasExpired($existingAttempt)) {
                return $existingAttempt;
            }

            $this->scoring->scoreAttempt($existingAttempt, []);
        }

        $attemptsUsed = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('enrollment_id', $enrollment->id)
            ->count();

        if ($quiz->attempts_allowed > 0 && $attemptsUsed >= $quiz->attempts_allowed) {
            throw new \DomainException('The maximum number of quiz attempts has been reached.');
        }

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'total_points' => (int) $quiz->questions->sum('points'),
            'time_spent_seconds' => 0,
            'status' => QuizAttemptStatus::InProgress,
        ]);

        return $this->loadAttempt($attempt->id);
    }

    /**
     * @param  array<int, array{question_id: int, answer_payload?: array<string, mixed>|null}>  $answers
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        return $this->scoring->scoreAttempt($attempt, $answers);
    }

    public function attemptsRemaining(Quiz $quiz, int $attemptsUsed): ?int
    {
        if ($quiz->attempts_allowed === 0) {
            return null;
        }

        return max(0, $quiz->attempts_allowed - $attemptsUsed);
    }

    /**
     * @return array{0: Course|null, 1: Lesson|null}
     */
    private function resolveContext(?int $courseId, ?int $lessonId): array
    {
        $lesson = null;
        $course = null;

        if ($lessonId) {
            $lesson = Lesson::query()->with('module')->findOrFail($lessonId);

            if (! $lesson->isQuiz()) {
                throw new \DomainException('Only quiz lessons can have an attached quiz.');
            }

            $course = Course::query()->findOrFail($lesson->module->course_id);
        }

        if ($courseId) {
            $course = Course::query()->findOrFail($courseId);
        }

        if ($lesson && $course && (int) $course->id !== (int) $lesson->module->course_id) {
            throw new \DomainException('The selected lesson does not belong to the selected course.');
        }

        return [$course, $lesson];
    }

    /**
     * @param  array<int, array{id?: int, label: string, is_correct: bool, sort_order?: int}>  $options
     */
    private function syncQuestionOptions(QuizQuestion $question, array $options): void
    {
        if ($question->question_type === QuizQuestionType::ShortAnswer) {
            $question->options()->delete();

            return;
        }

        $retainIds = [];

        foreach (array_values($options) as $index => $option) {
            $record = $question->options()->updateOrCreate(
                [
                    'id' => $option['id'] ?? null,
                ],
                [
                    'label' => $option['label'],
                    'is_correct' => (bool) $option['is_correct'],
                    'sort_order' => $option['sort_order'] ?? ($index + 1),
                ],
            );

            $retainIds[] = $record->id;
        }

        $question->options()->whereNotIn('id', $retainIds)->delete();
    }

    /**
     * @param  array<int, array{id?: int, label: string, is_correct: bool, sort_order?: int}>  $options
     */
    private function validateQuestionOptions(QuizQuestionType $type, array $options): void
    {
        if ($type === QuizQuestionType::ShortAnswer) {
            return;
        }

        if (count($options) < 2) {
            throw new \DomainException('Choice-based questions require at least two options.');
        }

        $correctCount = collect($options)->where('is_correct', true)->count();

        if (in_array($type, [QuizQuestionType::MultipleChoice, QuizQuestionType::TrueFalse], true) && $correctCount !== 1) {
            throw new \DomainException('Single-answer questions must have exactly one correct option.');
        }

        if ($type === QuizQuestionType::TrueFalse && count($options) !== 2) {
            throw new \DomainException('True/false questions must include exactly two options.');
        }

        if ($type === QuizQuestionType::MultiSelect && $correctCount < 1) {
            throw new \DomainException('Multi-select questions must include at least one correct option.');
        }
    }

    private function resolveActiveEnrollment(User $user, Quiz $quiz): Enrollment
    {
        if (! $quiz->course_id) {
            throw new \DomainException('This quiz is not attached to a course.');
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $quiz->course_id)
            ->first();

        if (! $enrollment) {
            throw new \DomainException('You are not enrolled in this course.');
        }

        if (
            $enrollment->status === EnrollmentStatus::Active
            && $enrollment->due_at !== null
            && $enrollment->due_at->isPast()
        ) {
            $enrollment->status = EnrollmentStatus::Expired;
            $enrollment->save();
            $enrollment->refresh();
        }

        if ($enrollment->status !== EnrollmentStatus::Active) {
            throw new \DomainException('Only active enrollments can attempt quizzes.');
        }

        return $enrollment;
    }

    private function ensureQuizCanBeAttempted(Quiz $quiz): void
    {
        if (! $quiz->isPublished()) {
            throw new \DomainException('Only published quizzes can be attempted.');
        }

        if (! $quiz->course || $quiz->course->status !== CourseStatus::Published) {
            throw new \DomainException('Quizzes can only be attempted while their course is published.');
        }

        if ($quiz->questions->isEmpty()) {
            throw new \DomainException('This quiz does not have any questions yet.');
        }
    }

    private function attemptHasExpired(QuizAttempt $attempt): bool
    {
        return $attempt->status === QuizAttemptStatus::InProgress
            && $attempt->quiz->time_limit_minutes !== null
            && $attempt->started_at !== null
            && $attempt->started_at->copy()->addMinutes((int) $attempt->quiz->time_limit_minutes)->isPast();
    }
}
