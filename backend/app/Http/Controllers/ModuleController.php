<?php

namespace App\Http\Controllers;

use App\Http\Requests\Modules\ReorderModulesRequest;
use App\Http\Requests\Modules\StoreModuleRequest;
use App\Http\Requests\Modules\UpdateModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    public function index(int $courseId): JsonResponse
    {
        $course = $this->courses->findCourse($courseId);

        $this->authorize('view', $course);

        $modules = $course->modules()->withCount('lessons')->orderBy('sort_order')->get();

        return $this->success(ModuleResource::collection($modules)->resolve());
    }

    public function store(StoreModuleRequest $request, int $courseId): JsonResponse
    {
        $course = $this->courses->findCourse($courseId);

        $this->authorize('manageModules', $course);

        $module = $this->courses->createModule($course, $request->validated());

        return $this->success(
            new ModuleResource($module),
            'Module created successfully.',
            201,
        );
    }

    public function update(UpdateModuleRequest $request, int $id): JsonResponse
    {
        $module = $this->courses->findModule($id);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('manageModules', $course);

        $updated = $this->courses->updateModule($module, $request->validated());

        return $this->success(
            new ModuleResource($updated),
            'Module updated successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $module = $this->courses->findModule($id);
        $course = $this->courses->findCourse($module->course_id);

        $this->authorize('manageModules', $course);

        $this->courses->deleteModule($module);

        return $this->success(message: 'Module deleted successfully.');
    }

    public function reorder(ReorderModulesRequest $request, int $courseId): JsonResponse
    {
        $course = $this->courses->findCourse($courseId);

        $this->authorize('manageModules', $course);

        $this->courses->reorderModules($course, $request->validated('modules'));

        return $this->success(message: 'Modules reordered successfully.');
    }
}
