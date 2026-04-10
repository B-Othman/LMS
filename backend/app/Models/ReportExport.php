<?php

namespace App\Models;

use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportExport extends Model
{
    protected $fillable = [
        'tenant_id',
        'requested_by',
        'report_type',
        'filters',
        'format',
        'file_path',
        'status',
        'error_message',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'format' => ExportFormat::class,
            'status' => ExportStatus::class,
            'filters' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function downloadUrl(): ?string
    {
        if ($this->status !== ExportStatus::Ready || ! $this->file_path) {
            return null;
        }

        return Storage::disk((string) config('filesystems.default', 's3'))
            ->temporaryUrl($this->file_path, now()->addHour());
    }
}
