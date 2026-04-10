<?php

namespace App\Http\Controllers;

use App\Http\Requests\Courses\IndexCoursesRequest;
use App\Http\Requests\Courses\StoreCourseRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    public function index(IndexCoursesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $courses = $this->courses->paginateCourses($request->validated());

        return $this->success(
            CourseResource::collection($courses->getCollection())->resolve(),
            meta: [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
            ],
        );
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $course = $this->courses->createCourse($request->user()->id, $request->validated());

        return $this->success(
            (new CourseResource($course))->detailed(),
            'Course created successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $course = $this->courses->findCourse($id);

        $this->authorize('view', $course);

        return $this->success((new CourseResource($course))->detailed());
    }

    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        $course = $this->courses->findCourse($id);

        $this->authorize('update', $course);

        $updated = $this->courses->updateCourse($course, $request->validated());

        return $this->success(
            (new CourseResource($updated))->detailed(),
            'Course updated successfully.',
        );
    }

    public function destroy(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $course = $this->courses->findCourse($id);

        $this->authorize('delete', $course);

        try {
            $this->courses->deleteCourse($course, $request->user()->id);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(message: 'Course deleted successfully.');
    }

    public function publish(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $course = $this->courses->findCourse($id);

        $this->authorize('publish', $course);

        try {
            $updated = $this->courses->publish($course, $request->user()->id);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            (new CourseResource($updated))->detailed(),
            'Course published successfully.',
        );
    }

    public function archive(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $course = $this->courses->findCourse($id);

        $this->authorize('publish', $course);

        $updated = $this->courses->archive($course, $request->user()->id);

        return $this->success(
            (new CourseResource($updated))->detailed(),
            'Course archived successfully.',
        );
    }

    public function duplicate(int $id): JsonResponse
    {
        $course = $this->courses->findCourse($id);

        $this->authorize('create', Course::class);

        $newCourse = $this->courses->duplicate($course);

        return $this->success(
            (new CourseResource($newCourse))->detailed(),
            'Course duplicated successfully.',
            201,
        );
    }
}
