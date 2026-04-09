<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Services\CertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class QueueCourseCertificateEligibilityCheck implements ShouldQueue
{
    public function __construct(
        private readonly CertificateService $certificates,
    ) {}

    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->loadMissing('course', 'user');

        try {
            $certificate = $this->certificates->issueCertificate($enrollment);
        } catch (\Throwable $exception) {
            Log::warning('Certificate issuance failed after course completion.', [
                'enrollment_id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'user_id' => $enrollment->user_id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (! $certificate) {
            return;
        }

        Log::info('Certificate issuance queued after course completion.', [
            'certificate_id' => $certificate->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $enrollment->course_id,
            'user_id' => $enrollment->user_id,
            'template_id' => $certificate->template_id,
        ]);
    }
}
