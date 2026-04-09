<?php

namespace App\Http\Controllers;

use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;

class PublicCertificateVerificationController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
    ) {}

    public function show(string $verificationCode): JsonResponse
    {
        $certificate = $this->certificates->findByVerificationCode($verificationCode);
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : [];

        return $this->success([
            'verification_code' => $certificate->verification_code,
            'status' => $certificate->verificationStatus(),
            'learner_name' => (string) ($metadata['learner_name'] ?? $certificate->user?->full_name ?? 'Learner'),
            'course_title' => (string) ($metadata['course_title'] ?? $certificate->course?->title ?? 'Course'),
            'issued_at' => $certificate->issued_at?->toIso8601String(),
            'expires_at' => $certificate->expires_at?->toIso8601String(),
            'revoked_at' => $certificate->revoked_at?->toIso8601String(),
            'revoked_reason' => $certificate->revoked_reason,
        ]);
    }
}
