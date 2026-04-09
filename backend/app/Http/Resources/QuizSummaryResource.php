<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quiz */
class QuizSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'description' => $this->description,
            'pass_score' => (int) $this->pass_score,
            'time_limit_minutes' => $this->time_limit_minutes,
            'attempts_allowed' => (int) $this->attempts_allowed,
            'shuffle_questions' => $this->shuffle_questions,
            'show_results_to_learner' => $this->show_results_to_learner,
            'status' => $this->status->value,
            'question_count' => (int) ($this->questions_count ?? $this->questions?->count() ?? 0),
            'attempts_used' => $this->when(isset($this->attempts_used), (int) $this->attempts_used),
            'attempts_remaining' => $this->when(array_key_exists('attempts_remaining', $this->getAttributes()), $this->attempts_remaining),
            'latest_attempt' => $this->when(isset($this->latest_attempt), $this->latest_attempt),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
