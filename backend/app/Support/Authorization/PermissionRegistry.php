<?php

namespace App\Support\Authorization;

use App\Enums\RoleScope;

class PermissionRegistry
{
    /**
     * @return array<int, array{code: string, group: string, description: string}>
     */
    public static function permissions(): array
    {
        return [
            ['code' => 'users.view', 'group' => 'users', 'description' => 'View users within the current tenant.'],
            ['code' => 'users.create', 'group' => 'users', 'description' => 'Create new users within the current tenant.'],
            ['code' => 'users.update', 'group' => 'users', 'description' => 'Update user records within the current tenant.'],
            ['code' => 'users.delete', 'group' => 'users', 'description' => 'Delete or deactivate users within the current tenant.'],
            ['code' => 'users.import', 'group' => 'users', 'description' => 'Import users in bulk.'],
            ['code' => 'roles.view', 'group' => 'roles', 'description' => 'View available roles and their permissions.'],
            ['code' => 'roles.assign', 'group' => 'roles', 'description' => 'Assign roles to users.'],
            ['code' => 'courses.view', 'group' => 'courses', 'description' => 'View courses within the current tenant.'],
            ['code' => 'courses.create', 'group' => 'courses', 'description' => 'Create courses.'],
            ['code' => 'courses.update', 'group' => 'courses', 'description' => 'Update courses.'],
            ['code' => 'courses.delete', 'group' => 'courses', 'description' => 'Delete courses.'],
            ['code' => 'courses.publish', 'group' => 'courses', 'description' => 'Publish courses.'],
            ['code' => 'modules.manage', 'group' => 'modules', 'description' => 'Manage course modules.'],
            ['code' => 'lessons.manage', 'group' => 'lessons', 'description' => 'Manage course lessons.'],
            ['code' => 'enrollments.view', 'group' => 'enrollments', 'description' => 'View enrollments.'],
            ['code' => 'enrollments.create', 'group' => 'enrollments', 'description' => 'Create enrollments.'],
            ['code' => 'enrollments.delete', 'group' => 'enrollments', 'description' => 'Delete enrollments.'],
            ['code' => 'assessments.manage', 'group' => 'assessments', 'description' => 'Manage assessments.'],
            ['code' => 'assessments.grade', 'group' => 'assessments', 'description' => 'Grade assessment submissions.'],
            ['code' => 'certificates.view', 'group' => 'certificates', 'description' => 'View certificates.'],
            ['code' => 'certificates.issue', 'group' => 'certificates', 'description' => 'Issue certificates.'],
            ['code' => 'reports.view', 'group' => 'reports', 'description' => 'View reports.'],
            ['code' => 'reports.export', 'group' => 'reports', 'description' => 'Export reports.'],
            ['code' => 'settings.manage', 'group' => 'settings', 'description' => 'Manage platform settings.'],
            ['code' => 'audit.view', 'group' => 'audit', 'description' => 'View audit logs.'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_column(self::permissions(), 'code');
    }

    /**
     * @return array<int, array{name: string, slug: string, description: string, scope: string, permissions: list<string>}>
     */
    public static function roleDefinitions(): array
    {
        return [
            [
                'name' => 'System Admin',
                'slug' => 'system_admin',
                'description' => 'Global configuration, security, and auditing.',
                'scope' => RoleScope::System->value,
                'permissions' => ['*'],
            ],
            [
                'name' => 'Tenant Admin',
                'slug' => 'tenant_admin',
                'description' => 'Manage users, enrollments, reports, and tenant branding.',
                'scope' => RoleScope::Tenant->value,
                'permissions' => array_values(array_diff(self::codes(), ['settings.manage', 'audit.view'])),
            ],
            [
                'name' => 'Content Manager',
                'slug' => 'content_manager',
                'description' => 'Create and edit courses, modules, lessons, and assessments.',
                'scope' => RoleScope::Tenant->value,
                'permissions' => ['courses.*', 'modules.manage', 'lessons.manage', 'assessments.manage'],
            ],
            [
                'name' => 'Instructor',
                'slug' => 'instructor',
                'description' => 'Review learner progress and support assessments.',
                'scope' => RoleScope::Tenant->value,
                'permissions' => ['courses.view', 'enrollments.view', 'assessments.grade'],
            ],
            [
                'name' => 'Learner',
                'slug' => 'learner',
                'description' => 'Access enrolled courses and view personal certificates.',
                'scope' => RoleScope::Tenant->value,
                'permissions' => ['courses.view', 'certificates.view'],
            ],
        ];
    }

    /**
     * @param  list<string>  $patterns
     * @return list<string>
     */
    public static function expandPermissionPatterns(array $patterns): array
    {
        $codes = self::codes();
        $resolved = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                return $codes;
            }

            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -1);

                foreach ($codes as $code) {
                    if (str_starts_with($code, $prefix)) {
                        $resolved[] = $code;
                    }
                }

                continue;
            }

            $resolved[] = $pattern;
        }

        return array_values(array_unique($resolved));
    }
}
