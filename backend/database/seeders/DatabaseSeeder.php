<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'securecy'],
            ['name' => 'Securecy', 'status' => TenantStatus::Active],
        );

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            NotificationTemplateSeeder::class,
        ]);

        // Create default System Admin
        $admin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@securecy.com', 'tenant_id' => $tenant->id],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => 'password',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $systemAdminRole = Role::query()
            ->where('slug', 'system_admin')
            ->whereNull('tenant_id')
            ->first();

        if ($systemAdminRole && ! $admin->hasRole('system_admin')) {
            $admin->assignRole($systemAdminRole);
        }

        // Create default learner user
        $learner = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'learner@securecy.com', 'tenant_id' => $tenant->id],
            [
                'first_name' => 'Sample',
                'last_name' => 'Learner',
                'password' => 'password',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $learnerRole = Role::query()
            ->where('slug', 'learner')
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($learnerRole && ! $learner->hasRole('learner')) {
            $learner->assignRole($learnerRole);
        }
    }
}
