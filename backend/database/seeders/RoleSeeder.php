<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Learner', 'slug' => 'learner', 'description' => 'Browse and consume courses, take quizzes, view progress', 'is_system' => true],
            ['name' => 'Instructor', 'slug' => 'instructor', 'description' => 'Review learner progress, support assessments', 'is_system' => true],
            ['name' => 'Content Manager', 'slug' => 'content-manager', 'description' => 'Create and edit courses, modules, lessons, quizzes', 'is_system' => true],
            ['name' => 'Tenant Admin', 'slug' => 'tenant-admin', 'description' => 'Manage users, enrollments, reports, branding', 'is_system' => true],
            ['name' => 'System Admin', 'slug' => 'system-admin', 'description' => 'Global configuration, security, auditing', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
