<?php

namespace App\Http\Requests\Quizzes;

use App\Enums\QuizStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
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
            'description' => ['sometimes', 'nullable', 'string'],
            'pass_score' => ['sometimes', 'required', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'attempts_allowed' => ['sometimes', 'required', 'integer', 'min:0'],
            'shuffle_questions' => ['sometimes', 'required', 'boolean'],
            'show_results_to_learner' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', 'string', Rule::in(array_map(fn ($status) => $status->value, QuizStatus::cases()))],
        ];
    }
}
