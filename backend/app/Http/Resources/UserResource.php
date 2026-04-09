<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('roles.permissions', 'tenant');

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'status' => $this->status->value,
            'avatar_url' => $this->avatar_path,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'roles' => $this->roles->pluck('slug')->values()->all(),
            'role_ids' => $this->roles->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'permissions' => $this->allPermissions()->pluck('code')->values()->all(),
            'enrollment_count' => (int) ($this->enrollments_count ?? 0),
            'tenant' => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
