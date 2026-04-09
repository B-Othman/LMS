<?php

namespace App\Models;

use App\Enums\MediaVisibility;
use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'tenant_id',
        'uploaded_by',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'visibility',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'metadata' => 'array',
            'visibility' => MediaVisibility::class,
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        if ($this->visibility->isPublic()) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $this->path,
            now()->addMinutes((int) config('media.signed_url_expiry_minutes', 15)),
        );
    }

    public function thumbnailUrl(): ?string
    {
        $thumbnailPath = $this->thumbnailPath();

        if (! $thumbnailPath) {
            return null;
        }

        if ($this->visibility->isPublic()) {
            return Storage::disk($this->disk)->url($thumbnailPath);
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $thumbnailPath,
            now()->addMinutes((int) config('media.signed_url_expiry_minutes', 15)),
        );
    }

    public function thumbnailPath(): ?string
    {
        return is_array($this->metadata)
            ? ($this->metadata['thumbnail_path'] ?? null)
            : null;
    }
}
