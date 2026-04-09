<?php

namespace App\Http\Controllers;

use App\Http\Requests\Enrollments\IndexEnrollmentsRequest;
use App\Http\Requests\Enrollments\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
    ) {}

    public function index(IndexEnrollmentsRequest $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validated();

        $paginator = $user->hasPermission('enrollments.view')
            ? $this->enrollments->paginateEnrollments($filters)
            : $this->enrollments->paginateUserEnrollments($user, $filters);

        return $this->success(
            EnrollmentResource::collection($paginator->getCollection())->resolve(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $this->authorize('create', Enrollment::class);

        $data = $request->validated();
        $adminId = $request->user()->id;

        if (! empty($data['user_ids'])) {
            try {
                $result = $this->enrollments->batchEnroll(
                    array_map('intval', $data['user_ids']),
                    (int) $data['course_id'],
                    $adminId,
                    $data['due_at'] ?? null,
                );
            } catch (\DomainException $exception) {
                return $this->error($exception->getMessage(), 422, [
                    ['code' => 'enrollment_invalid', 'message' => $exception->getMessage()],
                ]);
            }

            return $this->success(
                $result,
                'Batch enrollment processed.',
                201,
            );
        }

        try {
            $enrollment = $this->enrollments->enroll(
                (int) $data['user_id'],
                (int) $data['course_id'],
                $adminId,
                $data['due_at'] ?? null,
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'enrollment_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new EnrollmentResource($enrollment),
            'Enrollment created successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $enrollment = $this->enrollments->findEnrollment($id);
        $user = request()->user();

        if ($user->id !== $enrollment->user_id) {
            $this->authorize('view', $enrollment);
        }

        $this->enrollments->attachProgressSummary($enrollment);

        return $this->success(new EnrollmentResource($enrollment));
    }

    public function destroy(int $id): JsonResponse
    {
        $enrollment = $this->enrollments->findEnrollment($id);

        $this->authorize('delete', $enrollment);

        try {
            $dropped = $this->enrollments->drop($enrollment, request()->user()->id);
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'enrollment_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new EnrollmentResource($dropped),
            'Enrollment dropped successfully.',
        );
    }
}
