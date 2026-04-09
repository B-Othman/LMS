<?php

use App\Exceptions\AuthException;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'tenant.resolve' => ResolveTenantContext::class,
            'tenant.active' => EnsureTenantIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Ensure all API responses follow { message, errors? } envelope
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'errors' => [['code' => 'unauthenticated', 'message' => 'Authentication required.']],
                ], 401);
            }
        });

        $exceptions->render(function (AuthException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [['code' => $e->getErrorCode(), 'message' => $e->getMessage()]],
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    'errors' => [[
                        'code' => 'authorization_denied',
                        'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    ]],
                ], 403);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errors = [];
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = ['code' => 'validation_error', 'message' => $message, 'field' => $field];
                    }
                }

                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $errors,
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'errors' => [['code' => 'not_found', 'message' => 'The requested resource was not found.']],
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'An error occurred.',
                    'errors' => [['code' => 'http_error', 'message' => $e->getMessage()]],
                ], $e->getStatusCode());
            }
        });
    })->create();
