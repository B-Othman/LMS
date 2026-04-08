<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'role' => ['sometimes', 'string', 'exists:roles,slug'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $exists = User::withoutGlobalScopes()
                ->where('tenant_id', $this->input('tenant_id'))
                ->where('email', $this->input('email'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('email', 'A user with this email already exists in this organization.');
            }
        });
    }
}
