<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\CertificateService;
use App\Support\Certificates\CertificateTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateCertificatePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $certificateId,
    ) {}

    public function handle(
        CertificateTemplateRenderer $renderer,
        CertificateService $certificates,
    ): void {
        $certificate = Certificate::withoutGlobalScopes()
            ->with(['template', 'user', 'course', 'enrollment'])
            ->find($this->certificateId);

        if (! $certificate || ! $certificate->template || $certificate->revoked_at !== null) {
            return;
        }

        $pdf = $renderer->renderPdf($certificate->template, $renderer->dataForCertificate($certificate));
        $path = sprintf(
            'tenants/%d/certificates/%s/%d.pdf',
            $certificate->tenant_id,
            now()->format('Y'),
            $certificate->id,
        );

        Storage::disk((string) config('certificates.disk', 's3'))->put($path, $pdf, [
            'visibility' => 'private',
            'ContentType' => 'application/pdf',
        ]);

        $certificate->forceFill([
            'file_path' => $path,
        ])->save();

        $certificates->notifyIssued(
            $certificate->fresh(['user', 'course', 'template']) ?? $certificate,
        );
    }
}
