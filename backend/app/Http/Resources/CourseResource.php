<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Course */
class CourseResource extends JsonResource
{
    private bool $detailed = false;

    public function detailed(): static
    {
        $this->detailed = true;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'status' => $this->status->value,
            'visibility' => $this->visibility->value,
            'thumbnail_url' => $this->thumbnail_path,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()->all()),
            'enrollment_count' => (int) ($this->enrollments_count ?? 0),
            'module_count' => (int) ($this->modules_count ?? 0),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'full_name' => $this->creator->full_name,
            ] : null),
            'certificate_template' => $this->whenLoaded('certificateTemplate', fn () => $this->certificateTemplate ? [
                'id' => $this->certificateTemplate->id,
                'name' => $this->certificateTemplate->name,
                'layout' => $this->certificateTemplate->layout->value,
                'status' => $this->certificateTemplate->status->value,
                'is_default' => $this->certificateTemplate->is_default,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->detailed) {
            $data['description'] = $this->description;
            $data['certificate_template_id'] = $this->certificate_template_id;
            $data['modules'] = ModuleResource::collection($this->modules)->resolve();
        }

        return $data;
    }
}
