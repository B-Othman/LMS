<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonContentController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
    ) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::query()
            ->with(['module', 'resources.mediaFile'])
            ->findOrFail($id);

        try {
            $payload = $this->progress->lessonContentForUser($request->user(), $lesson);
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'lesson_content_unavailable', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success($payload);
    }
}
