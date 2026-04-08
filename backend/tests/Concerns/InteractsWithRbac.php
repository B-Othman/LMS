<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

trait InteractsWithRbac
{
    protected function seedRbac(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }

    protected function assignRole(User $user, string $slug): Role
    {
        $role = Role::query()
            ->where('slug', $slug)
            ->where(function ($query) use ($user) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $user->tenant_id);
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 ELSE 1 END', [$user->tenant_id ?? 0])
            ->firstOrFail();

        $user->assignRole($role);

        return $role;
    }
}
