<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'channel',
        'subject',
        'body_html',
        'body_text',
        'data',
        'status',
        'sent_at',
        'read_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
            'data' => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNotIn('status', [NotificationStatus::Read->value]);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeInApp(Builder $query): Builder
    {
        return $query->where('channel', NotificationChannel::InApp->value);
    }
}
