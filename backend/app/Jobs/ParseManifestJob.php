<?php

namespace App\Jobs;

use App\Enums\PackageStatus;
use App\Models\ContentPackage;
use App\Services\ScormManifestParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ParseManifestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $packageId,
    ) {}

    public function handle(ScormManifestParser $parser): void
    {
        $package = ContentPackage::withoutGlobalScopes()->find($this->packageId);

        if (! $package) {
            return;
        }

        $tempPath = null;

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

            $manifestXml = $this->extractManifest($tempPath);
            $manifestData = $parser->parse($manifestXml);
            $manifestData['scos'] = $parser->enrichScoTitles($manifestXml, $manifestData['scos']);

            // Update package title from manifest
            $package->title = $manifestData['title'] ?: $package->title;
            $package->save();

            // Dispatch extraction with manifest data
            PackageExtractionJob::dispatch($package->id, $manifestData)->afterCommit();
        } catch (\Throwable $e) {
            Log::error('SCORM manifest parsing failed', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            $package->status = PackageStatus::Invalid;
            $package->error_message = 'Manifest parse error: ' . $e->getMessage();
            $package->save();
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function extractManifest(string $zipPath): string
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open ZIP for manifest extraction.');
        }

        // Find imsmanifest.xml (case-insensitive)
        $manifestIndex = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && strtolower($name) === 'imsmanifest.xml') {
                $manifestIndex = $i;
                break;
            }
        }

        if ($manifestIndex === false) {
            $zip->close();
            throw new \RuntimeException('imsmanifest.xml not found in ZIP.');
        }

        $content = $zip->getFromIndex($manifestIndex);
        $zip->close();

        if ($content === false || $content === '') {
            throw new \RuntimeException('imsmanifest.xml is empty or unreadable.');
        }

        return $content;
    }
}
