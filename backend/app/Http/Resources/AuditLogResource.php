<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'description' => $this->description,
            'changes' => $this->changes,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->toIso8601String(),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->full_name,
                'email' => $this->actor->email,
            ]),
        ];
    }
}
