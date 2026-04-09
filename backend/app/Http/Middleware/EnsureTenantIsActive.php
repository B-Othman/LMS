<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantContext->tenant();

        if ($tenant && $tenant->status === TenantStatus::Suspended) {
            return response()->json([
                'message' => 'This organization has been suspended.',
                'errors' => [
                    ['code' => 'tenant_inactive', 'message' => 'This organization has been suspended.'],
                ],
            ], 403);
        }

        return $next($request);
    }
}
