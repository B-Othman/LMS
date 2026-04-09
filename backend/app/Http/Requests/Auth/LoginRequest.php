<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'tenant_slug' => ['nullable', 'string', 'exists:tenants,slug'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('tenant_id') || $this->filled('tenant_slug')) {
                return;
            }

            $validator->errors()->add('tenant_id', 'A tenant id or tenant slug is required.');
        });
    }
}
