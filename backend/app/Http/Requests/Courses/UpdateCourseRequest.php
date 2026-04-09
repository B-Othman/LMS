<?php

namespace App\Http\Requests\Courses;

use App\Enums\CourseVisibility;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->tenantId();
        $courseId = $this->route('id');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('courses', 'slug')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                )->ignore($courseId),
            ],
            'description' => ['nullable', 'string', 'max:65535'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'visibility' => ['nullable', 'string', Rule::in(array_map(fn ($v) => $v->value, CourseVisibility::cases()))],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'certificate_template_id' => [
                'nullable',
                'integer',
                Rule::exists('certificate_templates', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId),
                ),
            ],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:course_tags,id'],
        ];
    }
}
