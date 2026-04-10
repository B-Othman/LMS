<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentCreated;
use App\Filters\EnrollmentFilters;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginateEnrollments(array $filters): LengthAwarePaginator
    {
        $this->expireDueEnrollments();

        $query = $this->baseQuery();

        (new EnrollmentFilters($filters))->apply($query);

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (Enrollment $enrollment) => $this->progress->prepareEnrollmentSummary($enrollment));

        return $paginator;
    }

    /** @param array<string, mixed> $filters */
    public function paginateUserEnrollments(User $user, array $filters): LengthAwarePaginator
    {
        $this->expireDueEnrollments();

        $query = $this->baseQuery()
            ->where('user_id', $user->id);

        (new EnrollmentFilters($filters))->apply($query);

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (Enrollment $enrollment) => $this->progress->prepareEnrollmentSummary($enrollment));

        return $paginator;
    }

    public function findEnrollment(int $id): Enrollment
    {
        $this->expireDueEnrollments();

        $enrollment = Enrollment::query()
            ->with([
                'user',
                'course.creator',
                'course.modules.lessons',
            ])
            ->findOrFail($id);

        return $this->progress->prepareEnrollmentDetail($enrollment);
    }

    public function enroll(int $userId, int $courseId, int $adminId, mixed $dueAt = null): Enrollment
    {
        return DB::transaction(function () use ($userId, $courseId, $adminId, $dueAt) {
            $user = User::query()->findOrFail($userId);
            $course = Course::query()->findOrFail($courseId);

            $this->assertCourseCanBeEnrolled($course);
            $this->assertUserMatchesCourseTenant($user, $course);

            if (Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
                throw new \DomainException('The learner is already enrolled in this course.');
            }

            $enrollment = Enrollment::query()->create([
                'tenant_id' => $course->tenant_id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_by' => $adminId,
                'enrolled_at' => now(),
                'due_at' => $dueAt ? Carbon::parse($dueAt) : null,
                'status' => EnrollmentStatus::Active,
                'progress_percent' => 0,
                'completed_lessons_count' => 0,
            ]);

            $this->audit->log(
                'enrollment.created',
                $enrollment,
                $adminId,
                $course->tenant_id,
                "User {$user->email} enrolled in \"{$course->title}\"",
            );

            DB::afterCommit(function () use ($enrollment) {
                event(new EnrollmentCreated(
                    $enrollment->loadMissing('user', 'course'),
                ));
            });

            return $this->progress->prepareEnrollmentSummary(
                $enrollment->loadMissing('user', 'course'),
            );
        });
    }

    /**
     * @param list<int> $userIds
     * @return array{
     *   success_count: int,
     *   failure_count: int,
     *   failures: list<array{user_id: int, message: string}>,
     *   enrollments: list<array{id: int, user_id: int, course_id: int, status: string}>
     * }
     */
    public function batchEnroll(array $userIds, int $courseId, int $adminId, mixed $dueAt = null): array
    {
        $course = Course::query()->findOrFail($courseId);
        $this->assertCourseCanBeEnrolled($course);

        $created = [];
        $failures = [];

        foreach (array_values(array_unique($userIds)) as $userId) {
            try {
                $created[] = $this->enroll((int) $userId, $courseId, $adminId, $dueAt);
            } catch (\Throwable $exception) {
                $failures[] = [
                    'user_id' => (int) $userId,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'success_count' => count($created),
            'failure_count' => count($failures),
            'failures' => $failures,
            'enrollments' => collect($created)->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'status' => $enrollment->status->value,
            ])->values()->all(),
        ];
    }

    public function drop(Enrollment $enrollment, int $adminId): Enrollment
    {
        if (! $enrollment->status->canBeDropped()) {
            throw new \DomainException('Completed or already dropped enrollments cannot be removed.');
        }

        Enrollment::query()
            ->whereKey($enrollment->id)
            ->update([
            'status' => EnrollmentStatus::Dropped,
            'completed_at' => null,
        ]);

        $this->audit->log(
            'enrollment.dropped',
            $enrollment,
            $adminId,
            $enrollment->tenant_id,
            "Enrollment dropped by admin {$adminId}",
        );

        return $this->progress->prepareEnrollmentSummary(
            $enrollment->refresh()->loadMissing('user', 'course'),
        );
    }

    /** @param array<string, mixed> $filters */
    public function listMyCourses(User $user, array $filters = []): Collection
    {
        $this->expireDueEnrollments();

        $courses = Enrollment::query()
            ->with(['course' => fn ($query) => $query->withCount('modules')])
            ->where('user_id', $user->id)
            ->whereNotIn('status', [EnrollmentStatus::Dropped->value])
            ->get();

        $courses->each(fn (Enrollment $enrollment) => $this->progress->prepareEnrollmentSummary($enrollment));

        return $this->sortMyCourses($courses, $filters);
    }

    public function findMyCourse(User $user, int $courseId): Enrollment
    {
        $this->expireDueEnrollments();

        $enrollment = Enrollment::query()
            ->with([
                'course.creator',
                'course.modules.lessons',
            ])
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereNotIn('status', [EnrollmentStatus::Dropped->value])
            ->firstOrFail();

        return $this->progress->prepareEnrollmentDetail($enrollment);
    }

    public function attachProgressSummary(Enrollment $enrollment): Enrollment
    {
        return $this->progress->attachProgressSummary($enrollment);
    }

    private function baseQuery(): Builder
    {
        return Enrollment::query()
            ->with(['user', 'course']);
    }

    private function assertCourseCanBeEnrolled(Course $course): void
    {
        if ($course->status !== CourseStatus::Published) {
            throw new \DomainException('Only published courses can accept enrollments.');
        }
    }

    private function assertUserMatchesCourseTenant(User $user, Course $course): void
    {
        if ((int) $user->tenant_id !== (int) $course->tenant_id) {
            throw new \DomainException('Learners can only be enrolled into courses within the same tenant.');
        }
    }

    private function expireDueEnrollments(): void
    {
        Enrollment::query()
            ->where('status', EnrollmentStatus::Active->value)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => EnrollmentStatus::Expired->value]);
    }

    /** @param array<string, mixed> $filters */
    private function sortMyCourses(Collection $courses, array $filters): Collection
    {
        $sortBy = (string) ($filters['sort_by'] ?? 'recently_accessed');
        $direction = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $sorted = $courses->sortBy(function (Enrollment $enrollment) use ($sortBy) {
            return match ($sortBy) {
                'due_at' => optional($enrollment->due_at)->timestamp ?? PHP_INT_MAX,
                'progress' => (int) ($enrollment->progress_summary['progress_percentage'] ?? $enrollment->progress_percent ?? 0),
                default => optional($enrollment->updated_at ?? $enrollment->enrolled_at)->timestamp ?? 0,
            };
        });

        return $direction === 'desc' ? $sorted->reverse()->values() : $sorted->values();
    }
}
