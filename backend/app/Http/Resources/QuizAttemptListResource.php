<?php

namespace App\Http\Resources;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuizAttempt */
class QuizAttemptListResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'status' => $this->status->value,
            'score' => $this->score !== null ? (float) $this->score : null,
            'total_points' => (int) $this->total_points,
            'passed' => $this->passed,
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'time_spent_seconds' => (int) $this->time_spent_seconds,
            'quiz' => $this->quiz ? [
                'id' => $this->quiz->id,
                'title' => $this->quiz->title,
                'lesson_id' => $this->quiz->lesson_id,
                'course_id' => $this->quiz->course_id,
                'show_results_to_learner' => $this->quiz->show_results_to_learner,
            ] : null,
            'course' => $this->quiz?->course ? [
                'id' => $this->quiz->course->id,
                'title' => $this->quiz->course->title,
                'slug' => $this->quiz->course->slug,
            ] : null,
        ];
    }
}
