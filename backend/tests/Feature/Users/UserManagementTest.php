<?php

namespace Tests\Feature\Users;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_admin_can_list_tenant_users_with_filters(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'admin@example.com']);
        $this->assignRole($admin, 'tenant_admin');

        $matchingUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Alice',
            'last_name' => 'Instructor',
            'email' => 'alice@example.com',
        ]);
        $this->assignRole($matchingUser, 'instructor');

        $nonMatchingUser = User::factory()->suspended()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Alice',
            'last_name' => 'Learner',
            'email' => 'alice-learner@example.com',
        ]);
        $this->assignRole($nonMatchingUser, 'learner');

        $otherTenantUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Alice',
            'last_name' => 'External',
            'email' => 'alice-external@example.com',
        ]);
        $this->assignRole($otherTenantUser, 'instructor');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/users?search=alice&status=active&role=instructor&sort_by=name&sort_dir=asc&per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', 'alice@example.com')
            ->assertJsonPath('data.0.enrollment_count', 0);
    }

    public function test_admin_can_create_user_assign_role_and_send_welcome_notification(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'instructor')
            ->firstOrFail();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/users', [
            'first_name' => 'Jamie',
            'last_name' => 'Stone',
            'email' => 'jamie.stone@example.com',
            'password' => 'Secret123!',
            'status' => 'active',
            'role_ids' => [$role->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'User created successfully.')
            ->assertJsonPath('data.email', 'jamie.stone@example.com')
            ->assertJsonPath('data.roles.0', 'instructor');

        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', 'jamie.stone@example.com')
            ->firstOrFail();

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        // Welcome notification is now handled by NotificationService (template-driven).
    }

    public function test_admin_can_view_user_detail(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $target = User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Rami',
            'last_name' => 'Editor',
        ]);
        $this->assignRole($target, 'content_manager');

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/users/{$target->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.full_name', 'Rami Editor')
            ->assertJsonPath('data.enrollment_count', 0)
            ->assertJsonPath('data.roles.0', 'content_manager');
    }

    public function test_admin_can_suspend_user_and_send_notification(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $target = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'suspend-me@example.com',
        ]);
        $this->assignRole($target, 'learner');

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/users/{$target->id}", [
            'first_name' => $target->first_name,
            'last_name' => $target->last_name,
            'email' => $target->email,
            'status' => 'suspended',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'User updated successfully.')
            ->assertJsonPath('data.status', 'suspended');

        $target->refresh();
        $this->assertSame('suspended', $target->status->value);
        Notification::assertSentTo($target, UserSuspendedNotification::class);
    }

    public function test_admin_can_soft_delete_user_and_revoke_tokens(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $target = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($target, 'learner');
        $target->createToken('test-token');

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/users/{$target->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_system_admin_can_switch_tenant_context_via_header(): void
    {
        $homeTenant = Tenant::factory()->create();
        $targetTenant = Tenant::factory()->create();
        $this->seedRbac();

        $systemAdmin = User::factory()->create([
            'tenant_id' => $homeTenant->id,
            'email' => 'system.admin@example.com',
        ]);
        $this->assignRole($systemAdmin, 'system_admin');

        $homeTenantUser = User::factory()->create([
            'tenant_id' => $homeTenant->id,
            'email' => 'home@example.com',
        ]);
        $this->assignRole($homeTenantUser, 'tenant_admin');

        $targetTenantUser = User::factory()->create([
            'tenant_id' => $targetTenant->id,
            'email' => 'target@example.com',
        ]);
        $this->assignRole($targetTenantUser, 'tenant_admin');

        Sanctum::actingAs($systemAdmin);

        $response = $this->withHeader('X-Tenant-ID', (string) $targetTenant->id)
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', 'target@example.com');
    }
}
