<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'type' => $this->type,
            'subject_template' => $this->subject_template,
            'body_html_template' => $this->body_html_template,
            'body_text_template' => $this->body_text_template,
            'channel' => $this->channel,
            'is_active' => $this->is_active,
            'is_tenant_override' => $this->tenant_id !== null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
