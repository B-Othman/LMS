<?php

namespace App\Http\Controllers;

use App\Http\Requests\Enrollments\IndexMyCoursesRequest;
use App\Http\Resources\LearnerCourseDetailResource;
use App\Http\Resources\LearnerCourseResource;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;

class MyCourseController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
    ) {}

    public function index(IndexMyCoursesRequest $request): JsonResponse
    {
        $courses = $this->enrollments->listMyCourses($request->user(), $request->validated());

        return $this->success(LearnerCourseResource::collection($courses)->resolve());
    }

    public function show(int $courseId): JsonResponse
    {
        $enrollment = $this->enrollments->findMyCourse(request()->user(), $courseId);

        return $this->success(new LearnerCourseDetailResource($enrollment));
    }
}
