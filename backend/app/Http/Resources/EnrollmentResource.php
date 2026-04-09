<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Enrollment */
class EnrollmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $summary = $this->progress_summary;
        $progressPercentage = is_array($summary)
            ? (int) ($summary['progress_percentage'] ?? $this->progress_percent ?? $this->status->progressPercentage())
            : (int) ($this->progress_percent ?? $this->status->progressPercentage());

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'enrolled_by' => $this->enrolled_by,
            'status' => $this->status->value,
            'enrolled_at' => $this->enrolled_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'progress_percentage' => $progressPercentage,
            'completed_lessons_count' => (int) ($this->completed_lessons_count ?? 0),
            'progress_summary' => $summary,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ] : null),
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'slug' => $this->course->slug,
                'thumbnail_url' => $this->course->thumbnail_path,
                'status' => $this->course->status->value,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
