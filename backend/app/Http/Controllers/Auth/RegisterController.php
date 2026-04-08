<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $user = $this->auth->register($request->validated());

        return $this->success(
            ['user' => new UserResource($user)],
            'User registered successfully.',
            201,
        );
    }
}
