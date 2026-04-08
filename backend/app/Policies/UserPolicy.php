<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;

class UserPolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $this->hasTenantPermission($user, 'users.view', $target->tenant_id);
    }

    public function create(User $user, ?int $tenantId = null): bool
    {
        return $this->hasTenantPermission($user, 'users.create', $tenantId);
    }

    public function update(User $user, User $target): bool
    {
        return $this->hasTenantPermission($user, 'users.update', $target->tenant_id);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->hasTenantPermission($user, 'users.delete', $target->tenant_id);
    }
}
