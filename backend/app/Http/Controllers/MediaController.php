<?php

namespace App\Http\Controllers;

use App\Enums\MediaVisibility;
use App\Http\Requests\Media\UploadMediaRequest;
use App\Http\Resources\MediaFileResource;
use App\Jobs\DeleteMediaFileJob;
use App\Models\MediaFile;
use App\Services\MediaUploadService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploads,
        private readonly TenantContext $tenantContext,
    ) {}

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $tenant = $this->tenantContext->tenant();

        if (! $tenant) {
            return $this->error('Tenant context is required.', 400, [
                ['code' => 'tenant_context_missing', 'message' => 'Tenant context is required.'],
            ]);
        }

        $visibility = MediaVisibility::tryFrom(
            $request->string('visibility')->toString() ?: MediaVisibility::PrivateAccess->value,
        ) ?? MediaVisibility::PrivateAccess;

        $mediaFile = $this->mediaUploads->upload(
            $request->file('file'),
            $visibility,
            $tenant,
            $request->user()?->id,
        );

        return $this->success(
            new MediaFileResource($mediaFile),
            'File uploaded successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $mediaFile = MediaFile::query()->findOrFail($id);

        return $this->success(new MediaFileResource($mediaFile));
    }

    public function download(int $id): JsonResponse|RedirectResponse
    {
        $mediaFile = MediaFile::query()->findOrFail($id);

        if ($mediaFile->visibility->isPublic()) {
            return redirect()->to($mediaFile->url());
        }

        $expiresAt = now()->addMinutes((int) config('media.signed_url_expiry_minutes', 15));

        return $this->success([
            'url' => $this->mediaUploads->generateSignedUrl($mediaFile, $expiresAt, true),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $mediaFile = MediaFile::query()->findOrFail($id);
        $thumbnailPath = $mediaFile->thumbnailPath();

        $mediaFile->delete();

        DeleteMediaFileJob::dispatch($mediaFile->disk, $mediaFile->path, $thumbnailPath)->afterCommit();

        return $this->success(message: 'Media file deleted successfully.');
    }
}
