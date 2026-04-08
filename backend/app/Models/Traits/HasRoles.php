<?php

namespace App\Models\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasAnyRole(string ...$slugs): bool
    {
        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('slug', $slug);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Permission>
     */
    public function allPermissions(): \Illuminate\Support\Collection
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id')
            ->values();
    }

    public function assignRole(string $slug, ?int $tenantId = null): void
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $tenantId ??= $this->tenant_id;

        $this->roles()->syncWithoutDetaching([
            $role->id => ['tenant_id' => $tenantId],
        ]);
    }

    public function removeRole(string $slug): void
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $this->roles()->detach($role->id);
    }
}
