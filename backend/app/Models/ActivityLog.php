<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null; // Logs are immutable — no updated_at

    protected $fillable = [
        'tenant_id',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'entity_id' => 'integer',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
