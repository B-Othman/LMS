<?php

namespace App\Models\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $slug): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains('slug', $slug);
    }

    public function hasAnyRole(string ...$slugs): bool
    {
        $this->loadMissing('roles');

        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    public function hasPermission(string $code): bool
    {
        return $this->hasAnyPermission([$code]);
    }

    public function hasAnyPermission(array $codes): bool
    {
        if ($codes === []) {
            return false;
        }

        return $this->allPermissions()
            ->pluck('code')
            ->intersect($codes)
            ->isNotEmpty();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        $this->loadMissing('roles.permissions');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id')
            ->values();
    }

    public function assignRole(Role|string $role): void
    {
        $role = $role instanceof Role ? $role : $this->resolveRole($role);

        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->unsetRelation('roles');
    }

    public function removeRole(Role|string $role): void
    {
        $role = $role instanceof Role ? $role : $this->resolveRole($role);

        $this->roles()->detach($role->id);
        $this->unsetRelation('roles');
    }

    protected function resolveRole(string $slug): Role
    {
        return Role::query()
            ->where('slug', $slug)
            ->where(function ($query) {
                $query->whereNull('tenant_id');

                if ($this->tenant_id !== null) {
                    $query->orWhere('tenant_id', $this->tenant_id);
                }
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 ELSE 1 END', [$this->tenant_id ?? 0])
            ->firstOrFail();
    }
}
