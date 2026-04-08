<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        Gate::authorize('create', [User::class, $request->integer('tenant_id')]);

        $user = $this->auth->register($request->validated());

        return $this->success(
            ['user' => new UserResource($user)],
            'User registered successfully.',
            201,
        );
    }
}
