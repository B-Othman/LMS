<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // Create default tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'securecy'],
            ['name' => 'Securecy', 'is_active' => true],
        );

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

        $systemAdminRole = Role::where('slug', 'system-admin')->first();
        if ($systemAdminRole && ! $admin->hasRole('system-admin')) {
            $admin->roles()->attach($systemAdminRole->id, ['tenant_id' => $tenant->id]);
        }
    }
}
