<?php

namespace App\Http\Requests\Enrollments;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexEnrollmentsRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(array_map(fn ($status) => $status->value, EnrollmentStatus::cases()))],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort_by' => ['nullable', 'string', Rule::in(['enrolled_at', 'due_at', 'status', 'created_at'])],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
