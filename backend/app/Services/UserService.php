<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\UserStatus;
use App\Filters\UserFilters;
use App\Models\User;
use App\Notifications\UserSuspendedNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserService
{
    private ?bool $hasEnrollmentsTable = null;

    public function __construct(
        private readonly RoleManagementService $roleManagement,
        private readonly TenantContext $tenantContext,
        private readonly NotificationService $notificationService,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateUsers(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        (new UserFilters($filters))->apply($query);

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findUser(int $id): User
    {
        return $this->baseQuery()->findOrFail($id);
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, password: string, status: string, role_ids: array<int, int|string>}  $data
     */
    public function createUser(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data) {
            $tenantId = $this->tenantId();

            $user = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => $data['status'],
            ]);

            $this->roleManagement->syncRoles($actor, $user, $data['role_ids']);

            $loginUrl = config('app.frontend_url', 'http://localhost:3000').'/login';
            DB::afterCommit(fn () => $this->notificationService->send(
                $user->id,
                NotificationType::Welcome,
                [
                    'user_name' => $user->full_name,
                    'login_url' => $loginUrl,
                ],
            ));

            $this->audit->log(
                'user.created',
                $user,
                $actor->id,
                $tenantId,
                "User {$user->email} created by {$actor->email}",
            );

            return $this->findUser($user->id);
        });
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, status: string}  $data
     */
    public function updateUser(User $actor, User $user, array $data): User
    {
        return DB::transaction(function () use ($actor, $user, $data) {
            $before = $user->only(['first_name', 'last_name', 'email', 'status']);
            $wasSuspended = $user->status === UserStatus::Suspended;

            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'status' => $data['status'],
            ]);

            $after = $user->only(['first_name', 'last_name', 'email', 'status']);
            $isSuspended = $user->status === UserStatus::Suspended;

            if (! $wasSuspended && $isSuspended) {
                DB::afterCommit(fn () => $user->notify(new UserSuspendedNotification));

                $this->audit->log(
                    'user.suspended',
                    $user,
                    $actor->id,
                    $user->tenant_id,
                    "User {$user->email} suspended by {$actor->email}",
                );
            } else {
                $this->audit->log(
                    'user.updated',
                    $user,
                    $actor->id,
                    $user->tenant_id,
                    "User {$user->email} updated by {$actor->email}",
                    $this->audit->diff($before, $after),
                );
            }

            return $this->findUser($user->id);
        });
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function syncUserRoles(User $actor, User $user, array $roleIds): User
    {
        $updated = $this->roleManagement->syncRoles($actor, $user, $roleIds);

        $this->audit->log(
            'user.role_assigned',
            $user,
            $actor->id,
            $user->tenant_id,
            "Roles updated for {$user->email} by {$actor->email}",
            ['role_ids' => $roleIds],
        );

        return $updated;
    }

    public function deleteUser(User $actor, User $user): void
    {
        DB::transaction(function () use ($actor, $user) {
            $this->audit->log(
                'user.deleted',
                $user,
                $actor->id,
                $user->tenant_id,
                "User {$user->email} deleted by {$actor->email}",
            );

            $user->tokens()->delete();
            $user->delete();
        });
    }

    private function baseQuery(): Builder
    {
        $query = User::query()
            ->with(['roles', 'tenant']);

        if ($this->hasEnrollmentsTable()) {
            $query->withCount('enrollments');
        }

        return $query;
    }

    private function hasEnrollmentsTable(): bool
    {
        return $this->hasEnrollmentsTable ??= Schema::hasTable('enrollments');
    }

    private function tenantId(): int
    {
        return (int) ($this->tenantContext->tenantId() ?? 0);
    }
}
