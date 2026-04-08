<?php

namespace Database\Seeders;

use App\Enums\RoleScope;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = Permission::query()->pluck('id', 'code');

        foreach (PermissionRegistry::roleDefinitions() as $definition) {
            if ($definition['scope'] === RoleScope::System->value) {
                $role = Role::query()->updateOrCreate(
                    ['tenant_id' => null, 'slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'scope' => $definition['scope'],
                    ],
                );

                $role->permissions()->sync(
                    $this->permissionIdsFor($definition['permissions'], $permissionIds->all()),
                );
            }
        }

        $tenantRoleDefinitions = array_filter(
            PermissionRegistry::roleDefinitions(),
            fn (array $definition) => $definition['scope'] === RoleScope::Tenant->value,
        );

        foreach (Tenant::query()->get() as $tenant) {
            foreach ($tenantRoleDefinitions as $definition) {
                $role = Role::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'scope' => $definition['scope'],
                    ],
                );

                $role->permissions()->sync(
                    $this->permissionIdsFor($definition['permissions'], $permissionIds->all()),
                );
            }
        }
    }

    /**
     * @param  list<string>  $patterns
     * @param  array<string, int>  $permissionIds
     * @return list<int>
     */
    private function permissionIdsFor(array $patterns, array $permissionIds): array
    {
        return array_values(array_map(
            fn (string $code) => $permissionIds[$code],
            PermissionRegistry::expandPermissionPatterns($patterns),
        ));
    }
}
