<?php

namespace App\Http\Requests\Quizzes;

use App\Enums\QuizQuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question_type' => ['required', 'string', Rule::in(array_map(fn ($type) => $type->value, QuizQuestionType::cases()))],
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['sometimes', 'integer', 'exists:question_options,id'],
            'options.*.label' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
