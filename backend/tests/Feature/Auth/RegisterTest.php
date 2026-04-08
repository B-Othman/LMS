<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->seedRbac();

        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->assignRole($this->admin, 'system_admin');
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
        $this->assignRole($learner, 'learner');

        Sanctum::actingAs($learner);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('errors.0.code', 'missing_role');
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
