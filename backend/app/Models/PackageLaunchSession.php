<?php

namespace App\Models;

use App\Enums\LaunchSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PackageLaunchSession extends Model
{
    protected $fillable = [
        'package_version_id',
        'enrollment_id',
        'user_id',
        'launched_at',
        'closed_at',
        'duration_seconds',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => LaunchSessionStatus::class,
            'launched_at' => 'datetime',
            'closed_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function packageVersion(): BelongsTo
    {
        return $this->belongsTo(ContentPackageVersion::class, 'package_version_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runtimeState(): HasOne
    {
        return $this->hasOne(ScormRuntimeState::class, 'launch_session_id');
    }
}
