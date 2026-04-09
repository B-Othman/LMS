<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quizzes\ReorderQuizQuestionsRequest;
use App\Http\Requests\Quizzes\StoreQuizQuestionRequest;
use App\Http\Requests\Quizzes\UpdateQuizQuestionRequest;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;

class QuizQuestionController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes,
    ) {}

    public function store(StoreQuizQuestionRequest $request, int $id): JsonResponse
    {
        $quiz = $this->quizzes->findQuiz($id);
        $this->authorizeQuiz($quiz);

        try {
            $this->quizzes->addQuestion($quiz, $request->validated());
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'quiz_question_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new QuizResource($this->quizzes->findQuiz($quiz->id)),
            'Question added successfully.',
        );
    }

    public function update(UpdateQuizQuestionRequest $request, int $id): JsonResponse
    {
        $question = $this->quizzes->findQuestion($id);
        $this->authorizeQuiz($question->quiz);

        try {
            $this->quizzes->updateQuestion($question, $request->validated());
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'quiz_question_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new QuizResource($this->quizzes->findQuiz($question->quiz_id)),
            'Question updated successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $question = $this->quizzes->findQuestion($id);
        $this->authorizeQuiz($question->quiz);

        $quizId = $question->quiz_id;

        $this->quizzes->deleteQuestion($question);

        return $this->success(
            new QuizResource($this->quizzes->findQuiz($quizId)),
            'Question deleted successfully.',
        );
    }

    public function reorder(ReorderQuizQuestionsRequest $request, int $id): JsonResponse
    {
        $quiz = $this->quizzes->findQuiz($id);
        $this->authorizeQuiz($quiz);

        $this->quizzes->reorderQuestions($quiz, $request->validated('questions'));

        return $this->success(
            new QuizResource($this->quizzes->findQuiz($quiz->id)),
            'Questions reordered successfully.',
        );
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
