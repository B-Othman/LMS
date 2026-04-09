<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\Tenancy\ResolvesAuthTenant;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly ResolvesAuthTenant $tenantResolver,
    ) {}

    public function store(LoginRequest $request): JsonResponse
    {
        $tenant = $this->tenantResolver->resolve(
            $request->integer('tenant_id') ?: null,
            $request->string('tenant_slug')->toString(),
        );

        try {
            $result = $this->auth->login(
                $request->validated('email'),
                $request->validated('password'),
                (int) $tenant?->id,
            );
        } catch (AuthException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), [
                ['code' => $e->getErrorCode(), 'message' => $e->getMessage()],
            ]);
        }

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }
}
