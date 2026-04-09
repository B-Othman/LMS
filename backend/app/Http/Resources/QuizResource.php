<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quiz */
class QuizResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'description' => $this->description,
            'pass_score' => (int) $this->pass_score,
            'time_limit_minutes' => $this->time_limit_minutes,
            'attempts_allowed' => (int) $this->attempts_allowed,
            'shuffle_questions' => $this->shuffle_questions,
            'show_results_to_learner' => $this->show_results_to_learner,
            'status' => $this->status->value,
            'question_count' => (int) ($this->questions_count ?? $this->questions->count()),
            'questions' => $this->questions->map(fn ($question) => [
                'id' => $question->id,
                'quiz_id' => $question->quiz_id,
                'question_type' => $question->question_type->value,
                'prompt' => $question->prompt,
                'explanation' => $question->explanation,
                'points' => (int) $question->points,
                'sort_order' => (int) $question->sort_order,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'question_id' => $option->question_id,
                    'label' => $option->label,
                    'is_correct' => $option->is_correct,
                    'sort_order' => (int) $option->sort_order,
                ])->values()->all(),
                'created_at' => $question->created_at?->toIso8601String(),
                'updated_at' => $question->updated_at?->toIso8601String(),
            ])->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
