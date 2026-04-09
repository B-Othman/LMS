<?php

namespace App\Http\Requests\Categories;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->tenantId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('course_categories', 'slug')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                ),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
