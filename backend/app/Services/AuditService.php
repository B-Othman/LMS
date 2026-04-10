<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AuditService
{
    /**
     * Log an action.
     *
     * @param  array<string, mixed>|null  $changes  before/after diff: ['before' => [...], 'after' => [...]]
     */
    public function log(
        string $action,
        ?Model $entity,
        ?int $actorUserId,
        ?int $tenantId,
        ?string $description = null,
        ?array $changes = null,
    ): void {
        $ipAddress = null;
        $userAgent = null;

        if (App::bound('request')) {
            /** @var Request $request */
            $request = App::make('request');
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();
        }

        ActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entity ? $this->entityType($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description ?? $this->defaultDescription($action, $entity),
            'changes' => $changes,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    /**
     * Build a before/after diff from two attribute arrays.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{before: array<string, mixed>, after: array<string, mixed>}
     */
    public function diff(array $before, array $after): array
    {
        $changedKeys = array_keys(array_diff_assoc($after, $before));

        return [
            'before' => array_intersect_key($before, array_flip($changedKeys)),
            'after' => array_intersect_key($after, array_flip($changedKeys)),
        ];
    }

    private function entityType(Model $entity): string
    {
        $class = class_basename($entity);

        return match ($class) {
            'User' => 'user',
            'Course' => 'course',
            'Enrollment' => 'enrollment',
            'Certificate' => 'certificate',
            'Quiz', 'QuizAttempt' => 'quiz',
            'ReportExport' => 'export',
            default => mb_strtolower($class),
        };
    }

    private function defaultDescription(string $action, ?Model $entity): string
    {
        return match ($action) {
            'user.created' => 'User account created',
            'user.updated' => 'User account updated',
            'user.deleted' => 'User account deleted',
            'user.suspended' => 'User account suspended',
            'user.login' => 'User logged in',
            'user.logout' => 'User logged out',
            'user.role_assigned' => 'Role assigned to user',
            'user.role_removed' => 'Role removed from user',
            'course.created' => 'Course created',
            'course.updated' => 'Course updated',
            'course.published' => 'Course published',
            'course.archived' => 'Course archived',
            'course.deleted' => 'Course deleted',
            'enrollment.created' => 'Learner enrolled in course',
            'enrollment.dropped' => 'Enrollment dropped',
            'certificate.issued' => 'Certificate issued',
            'certificate.revoked' => 'Certificate revoked',
            'quiz.attempt_submitted' => 'Quiz attempt submitted',
            'export.requested' => 'Report export requested',
            'settings.updated' => 'Settings updated',
            default => ucwords(str_replace(['.', '_'], [' — ', ' '], $action)),
        };
    }
}
