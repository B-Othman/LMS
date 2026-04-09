<?php

namespace App\Http\Requests\Lessons;

use App\Enums\LessonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', Rule::in(array_map(fn ($t) => $t->value, LessonType::cases()))],
            'content_html' => ['nullable', 'string'],
            'content_json' => ['nullable', 'array'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_previewable' => ['nullable', 'boolean'],
        ];
    }
}
