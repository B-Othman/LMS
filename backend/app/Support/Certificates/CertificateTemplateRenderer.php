<?php

namespace App\Support\Certificates;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateRenderer
{
    /** @var list<string> */
    public const PLACEHOLDERS = [
        'learner_name',
        'course_title',
        'completion_date',
        'certificate_id',
        'verification_code',
    ];

    /**
     * @param  array<string, string>  $data
     */
    public function renderPdf(CertificateTemplate $template, array $data): string
    {
        return Pdf::loadHTML($this->renderHtml($template, $data))
            ->setPaper('a4', $template->layout->paperOrientation())
            ->output();
    }

    /**
     * @param  array<string, string>  $data
     */
    public function renderHtml(CertificateTemplate $template, array $data): string
    {
        $contentHtml = $this->replacePlaceholders($template->content_html, $data);
        $backgroundDataUri = $this->backgroundImageDataUri($template);

        return <<<HTML
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <style>
      @page { margin: 0; }
      * { box-sizing: border-box; }
      body {
        margin: 0;
        padding: 0;
        color: #0f172a;
        font-family: DejaVu Sans, sans-serif;
        background: #f8fafc;
      }
      .certificate-page {
        position: relative;
        width: 100%;
        min-height: 100vh;
        overflow: hidden;
        background:
          radial-gradient(circle at top left, rgba(91, 146, 198, 0.22), rgba(248, 250, 252, 0.94) 48%, rgba(255, 255, 255, 1) 100%);
      }
      .certificate-background {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
      }
      .certificate-content {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        padding: 56px 64px;
      }
      .certificate-content h1,
      .certificate-content h2,
      .certificate-content h3,
      .certificate-content p {
        margin-top: 0;
      }
    </style>
  </head>
  <body>
    <main class="certificate-page">
      {$this->backgroundImageTag($backgroundDataUri)}
      <section class="certificate-content">
        {$contentHtml}
      </section>
    </main>
  </body>
</html>
HTML;
    }

    /**
     * @return array<string, string>
     */
    public function sampleData(): array
    {
        return [
            'learner_name' => (string) config('certificates.preview.learner_name', 'Avery Carter'),
            'course_title' => (string) config('certificates.preview.course_title', 'Applied Security Foundations'),
            'completion_date' => (string) config('certificates.preview.completion_date', now()->format('F j, Y')),
            'certificate_id' => (string) config('certificates.preview.certificate_id', 'PREVIEW-2026-001'),
            'verification_code' => (string) config('certificates.preview.verification_code', 'SCY-PREV-26A1'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function dataForCertificate(Certificate $certificate): array
    {
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : [];

        return [
            'learner_name' => (string) ($metadata['learner_name'] ?? $certificate->user?->full_name ?? 'Learner'),
            'course_title' => (string) ($metadata['course_title'] ?? $certificate->course?->title ?? 'Course'),
            'completion_date' => (string) ($metadata['completion_date'] ?? $certificate->issued_at?->format('F j, Y') ?? now()->format('F j, Y')),
            'certificate_id' => (string) ($metadata['certificate_id'] ?? 'CERT-'.$certificate->id),
            'verification_code' => (string) $certificate->verification_code,
        ];
    }

    /**
     * @param  array<string, string>  $data
     */
    public function replacePlaceholders(string $contentHtml, array $data): string
    {
        $rendered = $contentHtml;

        foreach (self::PLACEHOLDERS as $placeholder) {
            $value = e((string) ($data[$placeholder] ?? ''));
            $rendered = preg_replace('/{{\s*'.preg_quote($placeholder, '/').'\s*}}/', $value, $rendered) ?? $rendered;
        }

        return $rendered;
    }

    private function backgroundImageTag(?string $backgroundDataUri): string
    {
        if (! $backgroundDataUri) {
            return '';
        }

        return '<img class="certificate-background" src="'.$backgroundDataUri.'" alt="" />';
    }

    private function backgroundImageDataUri(CertificateTemplate $template): ?string
    {
        if (! $template->background_image_path) {
            return null;
        }

        $disk = (string) config('certificates.background_disk', config('certificates.disk', 's3'));

        try {
            $contents = Storage::disk($disk)->get($template->background_image_path);
            $mimeType = Storage::disk($disk)->mimeType($template->background_image_path) ?: 'image/jpeg';
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
