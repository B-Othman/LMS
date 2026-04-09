<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lessons\HeartbeatLessonRequest;
use App\Http\Resources\LearnerCourseDetailResource;
use App\Http\Resources\LessonProgressResource;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;

class MyLessonController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
    ) {}

    public function start(int $lessonId): JsonResponse
    {
        $lesson = Lesson::query()->with('module')->findOrFail($lessonId);

        try {
            $enrollment = $this->progress->startLesson(
                $this->progress->resolveActiveEnrollmentForLesson(request()->user(), $lesson),
                $lesson,
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'lesson_progress_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new LearnerCourseDetailResource($enrollment),
            'Lesson started successfully.',
        );
    }

    public function complete(int $lessonId): JsonResponse
    {
        $lesson = Lesson::query()->with('module')->findOrFail($lessonId);

        try {
            $enrollment = $this->progress->completeLesson(
                $this->progress->resolveActiveEnrollmentForLesson(request()->user(), $lesson),
                $lesson,
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'lesson_progress_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new LearnerCourseDetailResource($enrollment),
            'Lesson completed successfully.',
        );
    }

    public function heartbeat(HeartbeatLessonRequest $request, int $lessonId): JsonResponse
    {
        $lesson = Lesson::query()->with('module')->findOrFail($lessonId);

        try {
            $progress = $this->progress->recordHeartbeat(
                $this->progress->resolveActiveEnrollmentForLesson($request->user(), $lesson),
                $lesson,
                (int) $request->validated('seconds'),
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'lesson_progress_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new LessonProgressResource($progress),
            'Lesson heartbeat recorded successfully.',
        );
    }
}
