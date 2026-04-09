<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lessons\ReorderLessonsRequest;
use App\Http\Requests\Lessons\StoreLessonRequest;
use App\Http\Requests\Lessons\UpdateLessonRequest;
use App\Http\Resources\LessonResource;
use App\Models\Module;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    public function store(StoreLessonRequest $request, int $moduleId): JsonResponse
    {
        $module = $this->courses->findModule($moduleId);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('manageLessons', $course);

        $lesson = $this->courses->createLesson($module, $request->validated());

        return $this->success(
            new LessonResource($lesson),
            'Lesson created successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $lesson = $this->courses->findLesson($id);
        $module = $this->courses->findModule($lesson->module_id);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('view', $course);

        return $this->success(new LessonResource($lesson));
    }

    public function update(UpdateLessonRequest $request, int $id): JsonResponse
    {
        $lesson = $this->courses->findLesson($id);
        $module = $this->courses->findModule($lesson->module_id);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('manageLessons', $course);

        $updated = $this->courses->updateLesson($lesson, $request->validated());

        return $this->success(
            new LessonResource($updated),
            'Lesson updated successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $lesson = $this->courses->findLesson($id);
        $module = $this->courses->findModule($lesson->module_id);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('manageLessons', $course);

        $this->courses->deleteLesson($lesson);

        return $this->success(message: 'Lesson deleted successfully.');
    }

    public function reorder(ReorderLessonsRequest $request, int $moduleId): JsonResponse
    {
        $module = $this->courses->findModule($moduleId);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('manageLessons', $course);

        $this->courses->reorderLessons($module, $request->validated('lessons'));

        return $this->success(message: 'Lessons reordered successfully.');
    }
}
