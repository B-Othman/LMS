<?php

namespace App\Http\Requests\Quizzes;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.answer_payload' => ['nullable', 'array'],
            'answers.*.answer_payload.selected_option_ids' => ['nullable', 'array'],
            'answers.*.answer_payload.selected_option_ids.*' => ['integer', 'exists:question_options,id'],
            'answers.*.answer_payload.text' => ['nullable', 'string'],
        ];
    }
}
