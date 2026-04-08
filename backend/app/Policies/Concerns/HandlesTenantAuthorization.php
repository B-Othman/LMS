<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesTenantAuthorization
{
    protected function hasTenantPermission(User $user, string $permission, ?int $tenantId = null): bool
    {
        if (! $user->hasPermission($permission)) {
            return false;
        }

        if ($user->hasRole('system_admin') || $tenantId === null) {
            return true;
        }

        return (int) $user->tenant_id === $tenantId;
    }
}
