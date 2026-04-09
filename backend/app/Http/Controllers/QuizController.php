<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quizzes\StoreQuizRequest;
use App\Http\Requests\Quizzes\UpdateQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes,
    ) {}

    public function store(StoreQuizRequest $request): JsonResponse
    {
        $this->authorizeContext(
            $request->validated('course_id'),
            $request->validated('lesson_id'),
        );

        try {
            $quiz = $this->quizzes->createQuiz($request->validated());
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'quiz_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new QuizResource($quiz),
            'Quiz created successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $quiz = $this->quizzes->findQuiz($id);

        $this->authorizeQuiz($quiz);

        return $this->success(new QuizResource($quiz));
    }

    public function update(UpdateQuizRequest $request, int $id): JsonResponse
    {
        $quiz = $this->quizzes->findQuiz($id);

        $this->authorizeQuiz($quiz);

        $updated = $this->quizzes->updateQuiz($quiz, $request->validated());

        return $this->success(
            new QuizResource($updated),
            'Quiz updated successfully.',
        );
    }

    private function authorizeContext(?int $courseId, ?int $lessonId): void
    {
        if ($lessonId) {
            $lesson = Lesson::query()->with('module')->findOrFail($lessonId);
            $course = Course::query()->findOrFail($lesson->module->course_id);

            $this->authorize('manageLessons', $course);

            return;
        }

        if ($courseId) {
            $course = Course::query()->findOrFail($courseId);

            $this->authorize('manageLessons', $course);
        }
    }

    private function authorizeQuiz(Quiz $quiz): void
    {
        if ($quiz->course) {
            $this->authorize('manageLessons', $quiz->course);

            return;
        }

        if ($quiz->lesson?->module) {
            $course = Course::query()->findOrFail($quiz->lesson->module->course_id);
            $this->authorize('manageLessons', $course);
        }
    }
}
