<?php

namespace App\Http\Resources;

use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CertificateTemplate */
class CertificateTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'layout' => $this->layout->value,
            'background_image_path' => $this->background_image_path,
            'background_image_url' => $this->backgroundImageUrl(),
            'content_html' => $this->content_html,
            'is_default' => (bool) $this->is_default,
            'status' => $this->status->value,
            'issued_count' => (int) ($this->certificates_count ?? 0),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'full_name' => $this->creator->full_name,
                'email' => $this->creator->email,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
