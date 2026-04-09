<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class LessonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'title' => $this->title,
            'type' => $this->type->value,
            'content_html' => $this->content_html,
            'content_json' => $this->content_json,
            'duration_minutes' => $this->duration_minutes,
            'sort_order' => $this->sort_order,
            'is_previewable' => $this->is_previewable,
            'quiz' => $this->when(
                $this->relationLoaded('quiz'),
                fn () => $this->quiz ? (new QuizSummaryResource($this->quiz))->resolve() : null,
            ),
            'resources' => LessonResourceResource::collection($this->whenLoaded('resources', $this->resources, collect()))->resolve(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
