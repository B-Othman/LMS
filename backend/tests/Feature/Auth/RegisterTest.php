<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();

        Role::insert([
            ['name' => 'System Admin', 'slug' => 'system-admin', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Learner', 'slug' => 'learner', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $adminRole = Role::where('slug', 'system-admin')->first();
        $this->admin->roles()->attach($adminRole->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_admin_can_register_user(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('message', 'User registered successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_register_requires_admin_role(): void
    {
        $learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $learnerRole = Role::where('slug', 'learner')->first();
        $learner->roles()->attach($learnerRole->id, ['tenant_id' => $this->tenant->id]);

        Sanctum::actingAs($learner);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_register_rejects_duplicate_email_in_tenant(): void
    {
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@example.com',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_register_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(401);
    }
}
