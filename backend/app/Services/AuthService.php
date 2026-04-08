<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Exceptions\AuthException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @return array{user: User, token: string}
     *
     * @throws AuthException
     */
    public function login(string $email, string $password, int $tenantId): array
    {
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw AuthException::invalidCredentials();
        }

        if (! $user->status->canLogin()) {
            throw AuthException::accountNotActive($user->status);
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
            $role = Role::query()
                ->where('slug', $roleSlug)
                ->where(function ($query) use ($user) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $user->tenant_id);
                })
                ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 ELSE 1 END', [$user->tenant_id])
                ->first();

            if ($role) {
                $user->assignRole($role);
            }

            $user->load('roles.permissions');

            return $user;
        });
    }
}
