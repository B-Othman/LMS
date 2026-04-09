<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DeleteMediaFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly ?string $thumbnailPath = null,
    ) {}

    public function handle(): void
    {
        $paths = array_values(array_filter([$this->path, $this->thumbnailPath]));

        if ($paths === []) {
            return;
        }

        Storage::disk($this->disk)->delete($paths);
    }
}
