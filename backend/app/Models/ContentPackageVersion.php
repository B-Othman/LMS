<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPackageVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'package_id',
        'version_number',
        'extracted_path',
        'manifest_data',
        'launch_path',
        'sco_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'manifest_data' => 'array',
            'metadata' => 'array',
            'sco_count' => 'integer',
            'version_number' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ContentPackage::class, 'package_id');
    }

    public function launchSessions(): HasMany
    {
        return $this->hasMany(PackageLaunchSession::class, 'package_version_id');
    }
}
