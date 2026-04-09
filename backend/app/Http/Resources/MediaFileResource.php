<?php

namespace App\Http\Resources;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MediaFile */
class MediaFileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uploaded_by' => $this->uploaded_by,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'visibility' => $this->visibility->value,
            'metadata' => $this->metadata,
            'url' => $this->url(),
            'thumbnail_url' => $this->thumbnailUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
