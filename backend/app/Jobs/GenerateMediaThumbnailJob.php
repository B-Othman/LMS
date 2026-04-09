<?php

namespace App\Jobs;

use App\Models\MediaFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateMediaThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $mediaFileId,
    ) {}

    public function handle(): void
    {
        $mediaFile = MediaFile::query()->find($this->mediaFileId);

        if (! $mediaFile || ! str_starts_with($mediaFile->mime_type, 'image/')) {
            return;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return;
        }

        $contents = Storage::disk($mediaFile->disk)->get($mediaFile->path);
        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if (! $sourceWidth || ! $sourceHeight) {
            imagedestroy($source);

            return;
        }

        $targetWidth = min((int) config('media.image_thumbnail_width', 360), $sourceWidth);
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $thumbnail) {
            imagedestroy($source);

            return;
        }

        $background = imagecolorallocate($thumbnail, 255, 255, 255);
        imagefill($thumbnail, 0, 0, $background);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        $written = imagejpeg($thumbnail, null, (int) config('media.image_thumbnail_quality', 82));
        $binary = ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumbnail);

        if (! $written || ! is_string($binary)) {
            return;
        }

        $thumbnailPath = sprintf(
            '%s/thumbnails/%s-thumb.jpg',
            dirname($mediaFile->path),
            pathinfo($mediaFile->path, PATHINFO_FILENAME),
        );

        Storage::disk($mediaFile->disk)->put($thumbnailPath, $binary, [
            'visibility' => $mediaFile->visibility->value,
            'ContentType' => 'image/jpeg',
        ]);

        $metadata = $mediaFile->metadata ?? [];
        $metadata['thumbnail_path'] = $thumbnailPath;
        $metadata['thumbnail_mime_type'] = 'image/jpeg';
        $metadata['thumbnail_dimensions'] = [
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];

        $mediaFile->forceFill(['metadata' => $metadata])->save();
    }
}
