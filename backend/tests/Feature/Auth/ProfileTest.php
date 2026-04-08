<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_me_returns_authenticated_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'status',
                    'roles',
                    'permissions',
                    'tenant' => ['id', 'name', 'slug'],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_includes_roles_and_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, 'learner');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.roles.0', 'learner');

        $this->assertContains('courses.view', $response->json('data.permissions'));
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJsonStructure(['message', 'errors']);
    }
}
