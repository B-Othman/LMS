<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class UserRolePermissionTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_user_has_role_for_an_attached_role(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, 'tenant_admin');

        $this->assertTrue($user->hasRole('tenant_admin'));
        $this->assertFalse($user->hasRole('system_admin'));
    }

    public function test_user_has_permission_through_all_attached_roles(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, 'instructor');
        $this->assignRole($user, 'content_manager');

        $this->assertTrue($user->hasPermission('courses.view'));
        $this->assertTrue($user->hasPermission('courses.create'));
        $this->assertTrue($user->hasPermission('assessments.grade'));
        $this->assertFalse($user->hasPermission('settings.manage'));
    }
}
