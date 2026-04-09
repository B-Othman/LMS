<?php

namespace App\Http\Requests\Quizzes;

use App\Enums\QuizStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['nullable', 'integer', 'required_without:lesson_id', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'required_without:course_id', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'attempts_allowed' => ['nullable', 'integer', 'min:0'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'show_results_to_learner' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(array_map(fn ($status) => $status->value, QuizStatus::cases()))],
        ];
    }
}
