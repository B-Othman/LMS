<?php

namespace App\Http\Requests\Lessons;

use Illuminate\Foundation\Http\FormRequest;

class ReorderLessonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lessons' => ['required', 'array', 'min:1'],
            'lessons.*.id' => ['required', 'integer', 'exists:lessons,id'],
            'lessons.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
