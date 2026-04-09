<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Enrollment */
class LearnerCourseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $summary = $this->progress_summary ?? null;

        return [
            'enrollment_id' => $this->id,
            'status' => $this->status->value,
            'enrolled_at' => $this->enrolled_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'next_lesson_id' => $this->next_lesson_id,
            'last_accessed_lesson_id' => $this->last_accessed_lesson_id,
            'progress_percentage' => is_array($summary)
                ? (int) ($summary['progress_percentage'] ?? $this->progress_percent ?? $this->status->progressPercentage())
                : (int) ($this->progress_percent ?? $this->status->progressPercentage()),
            'progress_summary' => $summary,
            'course' => $this->course ? [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'slug' => $this->course->slug,
                'short_description' => $this->course->short_description,
                'thumbnail_url' => $this->course->thumbnail_path,
                'status' => $this->course->status->value,
                'visibility' => $this->course->visibility->value,
                'module_count' => (int) ($this->course->modules_count ?? 0),
            ] : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
