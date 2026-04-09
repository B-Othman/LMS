<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Enrollment */
class LearnerCourseDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'enrollment' => [
                'id' => $this->id,
                'status' => $this->status->value,
                'enrolled_at' => $this->enrolled_at?->toIso8601String(),
                'due_at' => $this->due_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
                'progress_percentage' => (int) ($this->progress_summary['progress_percentage'] ?? $this->progress_percent ?? $this->status->progressPercentage()),
                'completed_lessons_count' => (int) ($this->completed_lessons_count ?? 0),
                'progress_summary' => $this->progress_summary ?? null,
                'next_lesson_id' => $this->next_lesson_id,
                'last_accessed_lesson_id' => $this->last_accessed_lesson_id,
            ],
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'slug' => $this->course->slug,
                'description' => $this->course->description,
                'short_description' => $this->course->short_description,
                'thumbnail_url' => $this->course->thumbnail_path,
                'status' => $this->course->status->value,
                'visibility' => $this->course->visibility->value,
                'creator' => $this->course->creator ? [
                    'id' => $this->course->creator->id,
                    'full_name' => $this->course->creator->full_name,
                ] : null,
                'modules' => collect($this->course->modules)->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'course_id' => $module->course_id,
                        'title' => $module->title,
                        'description' => $module->description,
                        'sort_order' => $module->sort_order,
                        'lesson_count' => $module->lessons->count(),
                        'total_duration' => (int) $module->lessons->sum('duration_minutes'),
                        'lessons' => $module->lessons->map(function ($lesson) {
                            return [
                                'id' => $lesson->id,
                                'module_id' => $lesson->module_id,
                                'title' => $lesson->title,
                                'type' => $lesson->type->value,
                                'duration_minutes' => $lesson->duration_minutes,
                                'sort_order' => $lesson->sort_order,
                                'is_previewable' => $lesson->is_previewable,
                                'quiz' => $lesson->relationLoaded('quiz') && $lesson->quiz
                                    ? (new QuizSummaryResource($lesson->quiz))->resolve()
                                    : null,
                                'progress' => $lesson->progress,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ],
        ];
    }
}
