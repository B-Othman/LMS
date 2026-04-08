<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @return array{user: User, token: string}
     *
     * @throws \App\Exceptions\AuthException
     */
    public function login(string $email, string $password, int $tenantId): array
    {
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw \App\Exceptions\AuthException::invalidCredentials();
        }

        if (! $user->status->canLogin()) {
            throw \App\Exceptions\AuthException::accountNotActive($user->status);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth')->plainTextToken;

        $user->load('roles.permissions');

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * @param  array{tenant_id: int, first_name: string, last_name: string, email: string, password: string, role?: string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::withoutGlobalScopes()->create([
                'tenant_id' => $data['tenant_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => UserStatus::Active,
            ]);

            $roleSlug = $data['role'] ?? 'learner';
            $role = Role::where('slug', $roleSlug)->first();

            if ($role) {
                $user->roles()->attach($role->id, ['tenant_id' => $user->tenant_id]);
            }

            $user->load('roles.permissions');

            return $user;
        });
    }
}
