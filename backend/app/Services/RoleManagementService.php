<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RoleManagementService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function paginateVisibleRoles(User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return $this->visibleRolesQuery($actor)
            ->orderBy('scope')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getVisibleRole(User $actor, int $roleId): Role
    {
        return $this->visibleRolesQuery($actor)
            ->with('permissions')
            ->findOrFail($roleId);
    }

    public function assignRole(User $actor, User $target, Role $role): User
    {
        if (! $this->canAssignRole($actor, $target, $role)) {
            throw new AuthorizationException('You are not allowed to assign this role.');
        }

        $target->assignRole($role);

        return $target->load('roles.permissions', 'tenant');
    }

    /**
     * @param  array<int, int|string>  $roleIds
     */
    public function syncRoles(User $actor, User $target, array $roleIds): User
    {
        $roles = $this->visibleRolesQuery($actor)
            ->whereIn('id', array_map('intval', $roleIds))
            ->get();

        if ($roles->count() !== count(array_unique(array_map('intval', $roleIds)))) {
            throw new AuthorizationException('You are not allowed to assign one or more selected roles.');
        }

        if ($roles->contains(fn (Role $role) => ! $this->canAssignRole($actor, $target, $role))) {
            throw new AuthorizationException('You are not allowed to assign one or more selected roles.');
        }

        $target->roles()->sync($roles->pluck('id'));

        return $target->load('roles.permissions', 'tenant');
    }

    private function visibleRolesQuery(User $actor): Builder
    {
        $query = Role::query();
        $tenantId = $this->currentTenantId($actor);

        if ($actor->hasRole('system_admin')) {
            if ($tenantId !== null) {
                $query->where(function (Builder $builder) use ($tenantId) {
                    $builder->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenantId);
                });
            }

            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    private function canAssignRole(User $actor, User $target, Role $role): bool
    {
        if ($role->isSystemRole()) {
            return $actor->hasRole('system_admin');
        }

        if ($role->tenant_id !== $target->tenant_id) {
            return false;
        }

        if ($actor->hasRole('system_admin')) {
            return true;
        }

        $tenantId = $this->currentTenantId($actor);

        return $tenantId === $target->tenant_id
            && $tenantId === $role->tenant_id;
    }

    private function currentTenantId(User $actor): ?int
    {
        return $this->tenantContext->tenantId() ?? $actor->tenant_id;
    }
}
