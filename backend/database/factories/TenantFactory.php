<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'domain' => null,
            'logo_path' => null,
            'status' => TenantStatus::Active,
            'settings' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => TenantStatus::Suspended]);
    }
}
