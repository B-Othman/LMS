<?php

namespace App\Models;

use App\Enums\CertificateTemplateLayout;
use App\Enums\CertificateTemplateStatus;
use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CertificateTemplate extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'layout',
        'background_image_path',
        'content_html',
        'is_default',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'layout' => CertificateTemplateLayout::class,
            'is_default' => 'boolean',
            'status' => CertificateTemplateStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'certificate_template_id');
    }

    public function backgroundImageUrl(): ?string
    {
        if (! $this->background_image_path) {
            return null;
        }

        $disk = (string) config('certificates.background_disk', config('certificates.disk', 's3'));

        return Storage::disk($disk)->temporaryUrl(
            $this->background_image_path,
            now()->addMinutes((int) config('certificates.background_url_expiry_minutes', 30)),
        );
    }
}
