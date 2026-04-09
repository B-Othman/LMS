<?php

namespace App\Http\Controllers;

use App\Enums\QuizAttemptStatus;
use App\Http\Requests\Quizzes\SubmitQuizAttemptRequest;
use App\Http\Resources\QuizAttemptListResource;
use App\Http\Resources\QuizAttemptResource;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $attempts = $this->quizzes->listAttemptsForUser($request->user());

        return $this->success(QuizAttemptListResource::collection($attempts)->resolve());
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $quiz = $this->quizzes->findQuiz($id);

        try {
            $attempt = $this->quizzes->startAttempt($quiz, $request->user());
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'quiz_attempt_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new QuizAttemptResource($attempt),
            'Quiz attempt started successfully.',
            201,
        );
    }

    public function submit(SubmitQuizAttemptRequest $request, int $id): JsonResponse
    {
        $attempt = $this->quizzes->findAttemptForUser($request->user(), $id);

        try {
            $attempt = $this->quizzes->submitAttempt(
                $attempt,
                $request->validated('answers', []),
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'quiz_attempt_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        $showResults = $attempt->quiz->show_results_to_learner
            && $attempt->status !== QuizAttemptStatus::NeedsGrading;

        return $this->success(
            (new QuizAttemptResource($attempt))->withResults($showResults),
            'Quiz submitted successfully.',
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $attempt = $this->quizzes->findAttemptForUser($request->user(), $id);

        $showResults = $attempt->status !== QuizAttemptStatus::InProgress
            && $attempt->quiz->show_results_to_learner
            && $attempt->status !== QuizAttemptStatus::NeedsGrading;

        return $this->success(
            (new QuizAttemptResource($attempt))->withResults($showResults),
        );
    }
}
