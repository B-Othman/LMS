<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rate_limited_after_5_attempts(): void
    {
        $tenant = Tenant::factory()->create();

        $payload = [
            'email' => 'user@example.com',
            'password' => 'wrong',
            'tenant_id' => $tenant->id,
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', $payload);
        }

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(429);
    }

    public function test_forgot_password_rate_limited_after_5_attempts(): void
    {
        $tenant = Tenant::factory()->create();

        $payload = [
            'email' => 'user@example.com',
            'tenant_id' => $tenant->id,
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', $payload);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', $payload);

        $response->assertStatus(429);
    }
}
