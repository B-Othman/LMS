<?php

namespace App\Services;

use App\Enums\MediaVisibility;
use App\Jobs\GenerateMediaThumbnailJob;
use App\Models\MediaFile;
use App\Models\Tenant;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MediaUploadService
{
    /** @var array<string, string> */
    private const EXTENSIONS_BY_MIME = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @var list<string> */
    private const EXECUTABLE_MIME_TYPES = [
        'application/x-dosexec',
        'application/x-executable',
        'application/x-msdos-program',
        'application/x-msdownload',
        'application/x-httpd-php',
        'application/x-mach-binary',
        'application/x-sh',
        'application/x-php',
        'text/x-php',
        'text/x-shellscript',
    ];

    public function upload(
        UploadedFile $file,
        MediaVisibility $visibility,
        Tenant $tenant,
        ?int $uploadedBy = null,
    ): MediaFile {
        $mimeType = $this->detectMimeType($file);
        $this->assertAllowedMimeType($mimeType);

        $sizeBytes = (int) ($file->getSize() ?? 0);
        $this->assertAllowedSize($sizeBytes, $mimeType);

        $disk = (string) config('media.disk', 's3');
        $path = $this->buildStoragePath($tenant->id, $mimeType);
        $metadata = $this->extractMetadata($file, $mimeType);
        $originalFilename = $this->sanitizeOriginalFilename($file->getClientOriginalName());

        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        try {
            $stored = Storage::disk($disk)->put($path, $stream, [
                'visibility' => $visibility->value,
                'ContentType' => $mimeType,
            ]);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        try {
            $mediaFile = MediaFile::query()->create([
                'tenant_id' => $tenant->id,
                'uploaded_by' => $uploadedBy,
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'size_bytes' => $sizeBytes,
                'visibility' => $visibility,
                'metadata' => $metadata ?: null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        if ($this->isImageMimeType($mimeType)) {
            GenerateMediaThumbnailJob::dispatch($mediaFile->id)->afterCommit();
        }

        return $mediaFile->fresh() ?? $mediaFile;
    }

    public function generateSignedUrl(
        MediaFile $mediaFile,
        ?DateTimeInterface $expiry = null,
        bool $download = false,
        ?string $path = null,
    ): string {
        $options = [];

        if ($download) {
            $options['ResponseContentDisposition'] = sprintf(
                'attachment; filename="%s"',
                addcslashes($mediaFile->original_filename, '"\\'),
            );
            $options['ResponseContentType'] = $mediaFile->mime_type;
        }

        return Storage::disk($mediaFile->disk)->temporaryUrl(
            $path ?? $mediaFile->path,
            $expiry ?? now()->addMinutes((int) config('media.signed_url_expiry_minutes', 15)),
            $options,
        );
    }

    private function detectMimeType(UploadedFile $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file->getRealPath()) : false;

        if ($finfo) {
            finfo_close($finfo);
        }

        if (! is_string($mimeType) || $mimeType === '') {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file type could not be determined.'],
            ]);
        }

        return match ($mimeType) {
            'image/jpg' => 'image/jpeg',
            'application/x-pdf' => 'application/pdf',
            default => $mimeType,
        };
    }

    private function assertAllowedMimeType(string $mimeType): void
    {
        if (in_array($mimeType, self::EXECUTABLE_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'file' => ['Executable uploads are not allowed.'],
            ]);
        }

        if (! array_key_exists($mimeType, (array) config('media.allowed_mime_types', []))) {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file type is not supported.'],
            ]);
        }
    }

    private function assertAllowedSize(int $sizeBytes, string $mimeType): void
    {
        $maxBytes = (int) ((config('media.allowed_mime_types')[$mimeType] ?? 0));

        if ($maxBytes > 0 && $sizeBytes > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => [sprintf(
                    'The uploaded file exceeds the maximum size of %d MB for %s.',
                    (int) round($maxBytes / 1024 / 1024),
                    $this->categoryLabel($mimeType),
                )],
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function extractMetadata(UploadedFile $file, string $mimeType): array
    {
        $metadata = [
            'extension' => self::EXTENSIONS_BY_MIME[$mimeType],
        ];

        if (! $this->isImageMimeType($mimeType)) {
            return $metadata;
        }

        $dimensions = @getimagesize($file->getRealPath());

        if (is_array($dimensions)) {
            $metadata['dimensions'] = [
                'width' => $dimensions[0],
                'height' => $dimensions[1],
            ];
        }

        return $metadata;
    }

    private function buildStoragePath(int $tenantId, string $mimeType): string
    {
        $path = sprintf(
            'tenants/%d/media/%s/%s.%s',
            $tenantId,
            now()->format('Y/m'),
            (string) Str::uuid(),
            self::EXTENSIONS_BY_MIME[$mimeType],
        );

        if (Str::contains($path, ['../', '..\\']) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            throw ValidationException::withMessages([
                'file' => ['The generated storage path is invalid.'],
            ]);
        }

        return $path;
    }

    private function sanitizeOriginalFilename(string $originalFilename): string
    {
        $normalized = basename(str_replace('\\', '/', $originalFilename));

        if (
            Str::contains($normalized, ['../', '..\\'])
            || preg_match('/[\x00-\x1F\x7F]/u', $normalized)
        ) {
            throw ValidationException::withMessages([
                'file' => ['The original filename is invalid.'],
            ]);
        }

        $sanitized = trim($normalized);

        if ($sanitized === '') {
            return 'upload';
        }

        return Str::limit($sanitized, 255, '');
    }

    private function isImageMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    private function categoryLabel(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'video/') => 'videos',
            str_starts_with($mimeType, 'image/') => 'images',
            default => 'documents',
        };
    }
}
