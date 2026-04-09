<?php

namespace App\Services;

use App\Enums\CertificateTemplateStatus;
use App\Models\CertificateTemplate;
use App\Models\Tenant;
use App\Support\Certificates\CertificateTemplateRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateTemplateService
{
    /** @var array<string, string> */
    private const BACKGROUND_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly CertificateTemplateRenderer $renderer,
    ) {}

    /**
     * @return Collection<int, CertificateTemplate>
     */
    public function listTemplates(): Collection
    {
        return CertificateTemplate::query()
            ->with('creator')
            ->withCount('certificates')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function findTemplate(int $id): CertificateTemplate
    {
        return CertificateTemplate::query()
            ->with('creator')
            ->withCount('certificates')
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTemplate(
        Tenant $tenant,
        int $createdBy,
        array $data,
        ?UploadedFile $backgroundImage = null,
    ): CertificateTemplate {
        return DB::transaction(function () use ($tenant, $createdBy, $data, $backgroundImage) {
            $backgroundPath = $backgroundImage ? $this->storeBackgroundImage($backgroundImage, $tenant->id) : null;

            $template = CertificateTemplate::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'layout' => $data['layout'] ?? 'landscape',
                'background_image_path' => $backgroundPath,
                'content_html' => $data['content_html'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'status' => $data['status'] ?? CertificateTemplateStatus::Active->value,
                'created_by' => $createdBy,
            ]);

            $this->syncDefaultFlag($template);

            return $this->findTemplate($template->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTemplate(
        CertificateTemplate $template,
        array $data,
        ?UploadedFile $backgroundImage = null,
    ): CertificateTemplate {
        return DB::transaction(function () use ($template, $data, $backgroundImage) {
            $updates = [];
            $oldBackgroundPath = $template->background_image_path;

            foreach (['name', 'description', 'layout', 'content_html', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if (array_key_exists('is_default', $data)) {
                $updates['is_default'] = (bool) $data['is_default'];
            }

            if (! empty($data['clear_background_image'])) {
                $updates['background_image_path'] = null;
            }

            if ($backgroundImage) {
                $updates['background_image_path'] = $this->storeBackgroundImage($backgroundImage, $template->tenant_id);
            }

            $template->fill($updates);
            $template->save();

            $this->syncDefaultFlag($template);

            if (
                $oldBackgroundPath
                && (
                    (! empty($data['clear_background_image']) && $template->background_image_path === null)
                    || ($backgroundImage && $template->background_image_path !== $oldBackgroundPath)
                )
            ) {
                Storage::disk((string) config('certificates.background_disk', config('certificates.disk', 's3')))
                    ->delete($oldBackgroundPath);
            }

            return $this->findTemplate($template->id);
        });
    }

    public function deleteTemplate(CertificateTemplate $template): void
    {
        if ($template->certificates()->exists()) {
            throw new \DomainException('Templates that already issued certificates cannot be deleted.');
        }

        $backgroundPath = $template->background_image_path;
        $template->delete();

        if ($backgroundPath) {
            Storage::disk((string) config('certificates.background_disk', config('certificates.disk', 's3')))
                ->delete($backgroundPath);
        }
    }

    public function renderPreviewPdf(CertificateTemplate $template): string
    {
        return $this->renderer->renderPdf($template, $this->renderer->sampleData());
    }

    private function syncDefaultFlag(CertificateTemplate $template): void
    {
        if (! $template->is_default) {
            return;
        }

        CertificateTemplate::query()
            ->where('tenant_id', $template->tenant_id)
            ->whereKeyNot($template->id)
            ->update(['is_default' => false]);
    }

    private function storeBackgroundImage(UploadedFile $file, int $tenantId): string
    {
        $mimeType = $this->detectMimeType($file);

        if (! array_key_exists($mimeType, self::BACKGROUND_EXTENSIONS)) {
            throw ValidationException::withMessages([
                'background_image' => ['Only JPEG, PNG, and WEBP images are supported for certificate backgrounds.'],
            ]);
        }

        $path = sprintf(
            'tenants/%d/certificate-templates/backgrounds/%s.%s',
            $tenantId,
            (string) Str::uuid(),
            self::BACKGROUND_EXTENSIONS[$mimeType],
        );

        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'background_image' => ['The selected background image could not be read.'],
            ]);
        }

        try {
            Storage::disk((string) config('certificates.background_disk', config('certificates.disk', 's3')))
                ->put($path, $stream, [
                    'visibility' => 'private',
                    'ContentType' => $mimeType,
                ]);
        } finally {
            fclose($stream);
        }

        return $path;
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
                'background_image' => ['The selected background image type could not be determined.'],
            ]);
        }

        return match ($mimeType) {
            'image/jpg' => 'image/jpeg',
            default => $mimeType,
        };
    }
}
