<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — all routes are prefixed with /api/v1
|--------------------------------------------------------------------------
*/

// Public auth routes (rate-limited)
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::post('/reset-password', [ResetPasswordController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/register', [RegisterController::class, 'store'])
        ->middleware('role:system_admin,tenant_admin');

    Route::post('/auth/logout', [LogoutController::class, 'store']);

    // Profile
    Route::get('/me', [ProfileController::class, 'show']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');
    Route::get('/roles/{id}/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:roles.view');
    Route::post('/users/{id}/roles', [UserRoleController::class, 'store'])
        ->middleware('permission:roles.assign');
});
