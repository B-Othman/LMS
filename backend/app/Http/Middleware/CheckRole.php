<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthenticated', 'message' => 'Authentication required.'],
                ],
            ], 401);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'errors' => [
                ['code' => 'forbidden', 'message' => 'You do not have the required role.'],
            ],
        ], 403);
    }
}
