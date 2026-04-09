<?php

namespace App\Http\Resources;

use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LessonProgress */
class LessonProgressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'status' => $this->status->value,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'progress_percentage' => (int) $this->progress_percent,
            'last_accessed_at' => $this->last_accessed_at?->toIso8601String(),
            'time_spent_seconds' => (int) $this->time_spent_seconds,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
