<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Models\Traits\TenantAware;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'enrollment_id',
        'user_id',
        'course_id',
        'tenant_id',
        'template_id',
        'issued_at',
        'expires_at',
        'file_path',
        'verification_code',
        'revoked_at',
        'revoked_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $inner): void {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    public function status(): CertificateStatus
    {
        if ($this->revoked_at !== null) {
            return CertificateStatus::Revoked;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return CertificateStatus::Expired;
        }

        return CertificateStatus::Active;
    }

    public function verificationStatus(): string
    {
        return match ($this->status()) {
            CertificateStatus::Active => 'valid',
            CertificateStatus::Expired => 'expired',
            CertificateStatus::Revoked => 'revoked',
        };
    }

    public function downloadUrl(bool $download = true, ?DateTimeInterface $expiry = null): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        $options = [];

        if ($download) {
            $options['ResponseContentDisposition'] = sprintf(
                'attachment; filename="%s"',
                addcslashes($this->downloadFilename(), '"\\'),
            );
            $options['ResponseContentType'] = 'application/pdf';
        }

        return Storage::disk((string) config('certificates.disk', 's3'))->temporaryUrl(
            $this->file_path,
            $expiry ?? now()->addMinutes((int) config('certificates.signed_url_expiry_minutes', 15)),
            $options,
        );
    }

    public function downloadFilename(): string
    {
        $courseTitle = (string) (($this->metadata['course_title'] ?? null) ?: $this->course?->title ?: 'certificate');
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($courseTitle)) ?: 'certificate';

        return trim($slug, '-').'-certificate.pdf';
    }
}
