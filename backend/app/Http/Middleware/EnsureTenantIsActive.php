<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant && ! $user->tenant->is_active) {
            return response()->json([
                'errors' => [
                    ['code' => 'tenant_inactive', 'message' => 'This organization has been deactivated.'],
                ],
            ], 403);
        }

        return $next($request);
    }
}
