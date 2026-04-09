<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TenantAware
{
    public static function bootTenantAware(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id || ! app()->bound(TenantContext::class)) {
                return;
            }

            $tenantId = app(TenantContext::class)->tenantId();

            if ($tenantId !== null) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
