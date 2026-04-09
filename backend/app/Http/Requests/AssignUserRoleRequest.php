<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('role_id') && ! $this->has('role_ids')) {
            $this->merge([
                'role_ids' => [$this->integer('role_id')],
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    /**
     * @return list<int>
     */
    public function roleIds(): array
    {
        return array_values(array_map('intval', $this->input('role_ids', [])));
    }
}
