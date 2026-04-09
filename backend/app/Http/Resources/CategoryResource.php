<?php

namespace App\Http\Resources;

use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourseCategory */
class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'course_count' => (int) ($this->courses_count ?? 0),
            'children' => CategoryResource::collection($this->whenLoaded('children', $this->children, collect()))->resolve(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
