<?php

namespace App\Http\Requests\Enrollments;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:user_ids'],
            'user_ids' => ['nullable', 'array', 'min:1', 'required_without:user_id'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
