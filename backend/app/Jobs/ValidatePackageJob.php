<?php

namespace App\Jobs;

use App\Enums\PackageStatus;
use App\Models\ContentPackage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ValidatePackageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum uncompressed size: 500 MB */
    private const MAX_UNCOMPRESSED_BYTES = 524_288_000;

    /** @var list<string> Forbidden file extensions */
    private const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phps',
        'exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'zsh',
        'pl', 'py', 'rb', 'cgi', 'asp', 'aspx', 'jsp', 'jar',
        'dll', 'so', 'dylib', 'msi',
    ];

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $packageId,
    ) {}

    public function handle(): void
    {
        $package = ContentPackage::withoutGlobalScopes()->find($this->packageId);

        if (! $package) {
            return;
        }

        $package->status = PackageStatus::Validating;
        $package->save();

        $tempPath = null;

        try {
            $disk = (string) config('media.disk', 's3');
            $tempPath = tempnam(sys_get_temp_dir(), 'scorm_') . '.zip';

            // Download ZIP from storage to temp file
            $stream = Storage::disk($disk)->readStream($package->file_path);

            if ($stream === false) {
                throw new \RuntimeException('Could not read package from storage.');
            }

            $dest = fopen($tempPath, 'wb');
            if ($dest === false) {
                throw new \RuntimeException('Could not create temp file.');
            }

            stream_copy_to_stream($stream, $dest);
            fclose($dest);
            fclose($stream);

            $this->validate($package, $tempPath);
        } catch (\Throwable $e) {
            Log::error('SCORM package validation failed', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->status = PackageStatus::Invalid;
            $package->error_message = $e->getMessage();
            $package->save();
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function validate(ContentPackage $package, string $zipPath): void
    {
        $zip = new ZipArchive;

        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \RuntimeException("Invalid ZIP file (error code: {$result}).");
        }

        $totalUncompressed = 0;
        $hasManifest = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if (! $stat) {
                continue;
            }

            $name = $stat['name'];
            $uncompressedSize = (int) $stat['size'];

            // Path traversal check
            if (str_contains($name, '../') || str_contains($name, '..\\') || str_starts_with($name, '/') || str_starts_with($name, '\\')) {
                $zip->close();
                throw new \RuntimeException("Path traversal detected in archive entry: {$name}");
            }

            // Blocked extension check
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
                $zip->close();
                throw new \RuntimeException("Blocked file type in archive: {$name}");
            }

            $totalUncompressed += $uncompressedSize;

            if ($totalUncompressed > self::MAX_UNCOMPRESSED_BYTES) {
                $zip->close();
                throw new \RuntimeException('Package exceeds maximum uncompressed size of 500 MB.');
            }

            // Check for imsmanifest.xml at ZIP root (no subdirectory prefix)
            if (strtolower($name) === 'imsmanifest.xml') {
                $hasManifest = true;
            }
        }

        $zip->close();

        if (! $hasManifest) {
            throw new \RuntimeException('Missing imsmanifest.xml at the root of the ZIP archive.');
        }

        // Validation passed — dispatch manifest parsing
        ParseManifestJob::dispatch($package->id)->afterCommit();

        $package->status = PackageStatus::Validating;
        $package->error_message = null;
        $package->save();
    }
}
