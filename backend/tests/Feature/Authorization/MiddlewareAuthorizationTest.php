<?php

namespace Tests\Feature\Authorization;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class MiddlewareAuthorizationTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_permission_middleware_blocks_unauthorized_access(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($learner, 'learner');

        Sanctum::actingAs($learner);

        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(403)
            ->assertJsonPath('errors.0.code', 'missing_permission');
    }
}
