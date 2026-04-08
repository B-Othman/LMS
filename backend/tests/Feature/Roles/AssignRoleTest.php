<?php

namespace Tests\Feature\Roles;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class AssignRoleTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_admin_can_assign_roles(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $target = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'instructor')
            ->firstOrFail();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$target->id}/roles", [
            'role_id' => $role->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Role assigned successfully.');

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $target->id,
            'role_id' => $role->id,
        ]);
        $this->assertContains('instructor', $response->json('data.roles'));
    }

    public function test_learner_cannot_assign_roles(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $target = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($learner, 'learner');

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'instructor')
            ->firstOrFail();

        Sanctum::actingAs($learner);

        $response = $this->postJson("/api/v1/users/{$target->id}/roles", [
            'role_id' => $role->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('errors.0.code', 'missing_permission');
    }
}
