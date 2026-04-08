<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'first_name', 'last_name', 'email', 'status', 'roles'],
                    'token',
                ],
                'message',
            ]);

        $this->assertNotNull($response->json('data.token'));
    }

    public function test_login_with_invalid_password(): void
    {
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('errors.0.code', 'invalid_credentials');
    }

    public function test_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('errors.0.code', 'invalid_credentials');
    }

    public function test_login_with_inactive_account(): void
    {
        User::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'inactive@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('errors.0.code', 'account_not_active');
    }

    public function test_login_with_suspended_account(): void
    {
        User::factory()->suspended()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'suspended@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'password',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('errors.0.code', 'account_not_active');
    }

    public function test_login_updates_last_login_at(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'last_login_at' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'tenant_id' => $this->tenant->id,
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_login_validation_rejects_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.');
    }

    public function test_login_wrong_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'tenant_id' => $otherTenant->id,
        ]);

        $response->assertStatus(401);
    }
}
