<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return $this->success(message: 'Logged out successfully.');
    }
}
