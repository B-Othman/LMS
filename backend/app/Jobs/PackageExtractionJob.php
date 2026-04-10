<?php

namespace App\Jobs;

use App\Enums\PackageStatus;
use App\Models\ContentPackage;
use App\Models\ContentPackageVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PackageExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    /**
     * @param  array<string, mixed>  $manifestData
     */
    public function __construct(
        public readonly int $packageId,
        public readonly array $manifestData,
    ) {}

    public function handle(): void
    {
        $package = ContentPackage::withoutGlobalScopes()->find($this->packageId);

        if (! $package) {
            return;
        }

        $tempPath = null;
        $tempDir = null;

        try {
            $disk = (string) config('media.disk', 's3');
            $tempPath = tempnam(sys_get_temp_dir(), 'scorm_') . '.zip';

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

            // Determine version number
            $versionNumber = ContentPackageVersion::query()
                ->where('package_id', $package->id)
                ->max('version_number') + 1;

            $extractedPath = sprintf(
                'tenants/%d/packages/%d/v%d',
                $package->tenant_id,
                $package->id,
                $versionNumber,
            );

            $tempDir = sys_get_temp_dir() . '/scorm_extract_' . $package->id . '_' . time();
            mkdir($tempDir, 0755, true);

            $this->extractToTemp($tempPath, $tempDir);
            $this->uploadToStorage($tempDir, $extractedPath, $disk);

            $scos = $this->manifestData['scos'] ?? [];
            $launchPath = $this->manifestData['launch_path'] ?? ($scos[0]['href'] ?? '');
            $scoCount = count($scos);

            DB::transaction(function () use ($package, $versionNumber, $extractedPath, $launchPath, $scoCount): void {
                ContentPackageVersion::query()->create([
                    'package_id' => $package->id,
                    'version_number' => $versionNumber,
                    'extracted_path' => $extractedPath,
                    'manifest_data' => $this->manifestData,
                    'launch_path' => $launchPath,
                    'sco_count' => $scoCount,
                    'metadata' => [
                        'title' => $this->manifestData['title'] ?? '',
                        'description' => $this->manifestData['description'] ?? '',
                        'identifier' => $this->manifestData['identifier'] ?? '',
                        'version' => $this->manifestData['version'] ?? '',
                    ],
                ]);

                $package->status = PackageStatus::Valid;
                $package->error_message = null;
                $package->save();
            });
        } catch (\Throwable $e) {
            Log::error('SCORM package extraction failed', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->status = PackageStatus::Failed;
            $package->error_message = 'Extraction failed: ' . $e->getMessage();
            $package->save();
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            if ($tempDir && is_dir($tempDir)) {
                $this->rmdir($tempDir);
            }
        }
    }

    private function extractToTemp(string $zipPath, string $destDir): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open ZIP for extraction.');
        }

        $zip->extractTo($destDir);
        $zip->close();
    }

    private function uploadToStorage(string $tempDir, string $s3Prefix, string $disk): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            $relativePath = ltrim(
                str_replace($tempDir, '', $file->getRealPath()),
                DIRECTORY_SEPARATOR,
            );

            // Normalise Windows backslashes
            $relativePath = str_replace('\\', '/', $relativePath);

            $storagePath = $s3Prefix . '/' . $relativePath;
            $mimeType = mime_content_type($file->getRealPath()) ?: 'application/octet-stream';

            $stream = fopen($file->getRealPath(), 'rb');

            if ($stream === false) {
                throw new \RuntimeException("Could not open file: {$file->getRealPath()}");
            }

            try {
                Storage::disk($disk)->put($storagePath, $stream, [
                    'visibility' => 'private',
                    'ContentType' => $mimeType,
                ]);
            } finally {
                fclose($stream);
            }
        }
    }

    private function rmdir(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($dir);
    }
}
