<?php

namespace App\Http\Requests\Courses;

use App\Enums\CourseStatus;
use App\Enums\CourseVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_map(fn ($s) => $s->value, CourseStatus::cases()))],
            'visibility' => ['nullable', 'string', Rule::in(array_map(fn ($v) => $v->value, CourseVisibility::cases()))],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'sort_by' => ['nullable', 'string', Rule::in(['title', 'status', 'created_at', 'updated_at'])],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
