<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Filters\UserFilters;
use App\Models\User;
use App\Notifications\UserSuspendedNotification;
use App\Notifications\WelcomeUserNotification;
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

            $tenantName = $this->tenantContext->tenant()?->name ?? 'Securecy';
            DB::afterCommit(fn () => $user->notify(new WelcomeUserNotification($tenantName)));

            return $this->findUser($user->id);
        });
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, status: string}  $data
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $wasSuspended = $user->status === UserStatus::Suspended;

            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'status' => $data['status'],
            ]);

            if (! $wasSuspended && $user->status === UserStatus::Suspended) {
                DB::afterCommit(fn () => $user->notify(new UserSuspendedNotification));
            }

            return $this->findUser($user->id);
        });
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function syncUserRoles(User $actor, User $user, array $roleIds): User
    {
        return $this->roleManagement->syncRoles($actor, $user, $roleIds);
    }

    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
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
