<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RoleManagementService
{
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

    private function visibleRolesQuery(User $actor): Builder
    {
        $query = Role::query();

        if (! $actor->hasRole('system_admin')) {
            $query->where('tenant_id', $actor->tenant_id);
        }

        return $query;
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

        return $actor->tenant_id === $target->tenant_id
            && $actor->tenant_id === $role->tenant_id;
    }
}
