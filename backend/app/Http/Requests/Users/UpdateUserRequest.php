<?php

namespace App\Http\Requests\Users;

use App\Enums\UserStatus;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->currentTenantId();
        $userId = (int) $this->route('id');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => ['required', 'string', Rule::in(array_map(fn (UserStatus $status) => $status->value, UserStatus::cases()))],
        ];
    }

    private function currentTenantId(): ?int
    {
        return app(TenantContext::class)->tenantId() ?? $this->user()?->tenant_id;
    }
}
