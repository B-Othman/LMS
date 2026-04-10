<?php

namespace App\Models;

use App\Enums\PackageStandard;
use App\Enums\PackageStatus;
use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPackage extends Model
{
    use TenantAware;

    protected $fillable = [
        'tenant_id',
        'course_id',
        'title',
        'standard',
        'original_filename',
        'file_path',
        'file_size_bytes',
        'status',
        'error_message',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'standard' => PackageStandard::class,
            'status' => PackageStatus::class,
            'file_size_bytes' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContentPackageVersion::class, 'package_id')->orderBy('version_number');
    }

    public function latestVersion(): ?ContentPackageVersion
    {
        return $this->versions()->orderByDesc('version_number')->first();
    }
}
