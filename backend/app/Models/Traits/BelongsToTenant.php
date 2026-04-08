<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = static::resolveCurrentTenant()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenant->id);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id && $tenant = static::resolveCurrentTenant()) {
                $model->tenant_id = $tenant->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function resolveCurrentTenant(): ?Tenant
    {
        $user = auth()->user();

        if ($user && method_exists($user, 'tenant')) {
            return $user->tenant;
        }

        return null;
    }
}
