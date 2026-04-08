<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Authentication required.',
                'errors' => [
                    ['code' => 'unauthenticated', 'message' => 'Authentication required.'],
                ],
            ], 401);
        }

        if (! $user->hasAnyPermission($permissions)) {
            return response()->json([
                'message' => 'The required permission is missing.',
                'errors' => [
                    ['code' => 'missing_permission', 'message' => 'You do not have permission to perform this action.'],
                ],
            ], 403);
        }

        return $next($request);
    }
}
