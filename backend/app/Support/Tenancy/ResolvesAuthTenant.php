<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

class ResolvesAuthTenant
{
    public function resolve(?int $tenantId = null, ?string $tenantSlug = null): ?Tenant
    {
        if ($tenantId !== null) {
            return Tenant::query()->find($tenantId);
        }

        if ($tenantSlug !== null && $tenantSlug !== '') {
            return Tenant::query()->where('slug', $tenantSlug)->first();
        }

        return null;
    }
}
