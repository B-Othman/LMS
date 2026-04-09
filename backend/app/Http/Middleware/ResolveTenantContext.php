<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $tenantId = $user->tenant_id;
        $requestedTenantId = $request->headers->get('X-Tenant-ID');

        if ($requestedTenantId !== null) {
            if (! $user->hasRole('system_admin')) {
                return $this->errorResponse(
                    'Tenant switching is only available to system administrators.',
                    403,
                    'tenant_context_forbidden',
                );
            }

            $tenantId = (int) $requestedTenantId;
        }

        if ($tenantId === null) {
            return $next($request);
        }

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant) {
            return $this->errorResponse(
                'The requested tenant context could not be found.',
                404,
                'tenant_not_found',
            );
        }

        $this->tenantContext->setTenant($tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    private function errorResponse(string $message, int $status, string $code): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [
                ['code' => $code, 'message' => $message],
            ],
        ], $status);
    }
}
