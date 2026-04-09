<?php

namespace App\Http\Controllers;

use App\Http\Resources\LearnerCourseDetailResource;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyEnrollmentProgressController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
    ) {}

    public function show(Request $request, int $enrollmentId): JsonResponse
    {
        $enrollment = $this->progress->findUserEnrollmentProgress($request->user(), $enrollmentId);

        return $this->success(new LearnerCourseDetailResource($enrollment));
    }
}
