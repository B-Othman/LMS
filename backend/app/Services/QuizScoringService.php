<?php

namespace App\Services;

use App\Enums\QuizAttemptStatus;
use App\Enums\QuizQuestionType;
use App\Events\QuizCompleted;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class QuizScoringService
{
    public function __construct(
        private readonly ProgressService $progress,
    ) {}

    /**
     * @param  array<int, array{question_id: int, answer_payload?: array<string, mixed>|null}>  $answers
     */
    public function scoreAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            $attempt->loadMissing('quiz.lesson.module', 'quiz.questions.options', 'enrollment', 'answers');

            if ($attempt->status !== QuizAttemptStatus::InProgress) {
                throw new \DomainException('This quiz attempt has already been submitted.');
            }

            $submittedAnswers = collect($answers)->keyBy('question_id');
            $requiresManualGrading = false;
            $awardedPoints = 0;
            $totalPoints = (int) $attempt->quiz->questions->sum('points');

            foreach ($attempt->quiz->questions as $question) {
                $normalizedPayload = $this->normalizeAnswerPayload(
                    $question,
                    $submittedAnswers->get($question->id)['answer_payload'] ?? [],
                );

                [$isCorrect, $questionPoints, $needsManualGrading] = $this->scoreQuestion(
                    $question,
                    $normalizedPayload,
                );

                $requiresManualGrading = $requiresManualGrading || $needsManualGrading;

                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'answer_payload' => $normalizedPayload,
                        'is_correct' => $isCorrect,
                        'awarded_points' => $questionPoints,
                    ],
                );

                $awardedPoints += $questionPoints;
            }

            $attempt->submitted_at = now();
            $attempt->time_spent_seconds = max(
                (int) $attempt->time_spent_seconds,
                max(0, (int) $attempt->started_at?->diffInSeconds(now())),
            );
            $attempt->total_points = $totalPoints;

            if ($requiresManualGrading) {
                $attempt->status = QuizAttemptStatus::NeedsGrading;
                $attempt->score = null;
                $attempt->passed = null;
            } else {
                $attempt->status = QuizAttemptStatus::Graded;
                $attempt->score = $totalPoints > 0
                    ? round(($awardedPoints / $totalPoints) * 100, 2)
                    : 0;
                $attempt->passed = (float) $attempt->score >= (float) $attempt->quiz->pass_score;
            }

            $attempt->save();

            if ($attempt->passed && $attempt->quiz->lesson) {
                $this->progress->completeLesson(
                    $attempt->enrollment,
                    $attempt->quiz->lesson,
                );
            }

            DB::afterCommit(function () use ($attempt) {
                event(new QuizCompleted(
                    $attempt->fresh()?->loadMissing('quiz.course', 'quiz.lesson', 'answers') ?? $attempt->loadMissing('quiz.course', 'quiz.lesson', 'answers'),
                ));
            });

            return $attempt->fresh(['quiz.course', 'quiz.lesson', 'quiz.questions.options', 'answers']) ?? $attempt;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeAnswerPayload(QuizQuestion $question, array $payload): array
    {
        return match ($question->question_type) {
            QuizQuestionType::ShortAnswer => [
                'text' => trim((string) ($payload['text'] ?? '')),
            ],
            QuizQuestionType::MultiSelect => [
                'selected_option_ids' => collect($payload['selected_option_ids'] ?? [])
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn (int $value) => $value > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ],
            default => [
                'selected_option_ids' => collect([
                    ...((array) ($payload['selected_option_ids'] ?? [])),
                    $payload['selected_option_id'] ?? null,
                ])
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn (int $value) => $value > 0)
                    ->unique()
                    ->take(1)
                    ->values()
                    ->all(),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: bool|null, 1: int, 2: bool}
     */
    private function scoreQuestion(QuizQuestion $question, array $payload): array
    {
        if ($question->question_type === QuizQuestionType::ShortAnswer) {
            return [null, 0, true];
        }

        $correctOptionIds = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($value) => (int) $value)
            ->sort()
            ->values();

        $selectedOptionIds = collect($payload['selected_option_ids'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->sort()
            ->values();

        $isCorrect = $selectedOptionIds->values()->all() === $correctOptionIds->values()->all();

        return [
            $isCorrect,
            $isCorrect ? (int) $question->points : 0,
            false,
        ];
    }
}
