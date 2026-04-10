<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\CertificateIssued;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCertificateIssuedNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(CertificateIssued $event): void
    {
        $certificate = $event->certificate->loadMissing('user', 'course');

        if (! $certificate->user || ! $certificate->course) {
            return;
        }

        $downloadUrl = config('app.frontend_url', 'http://localhost:3000')
            .'/certificates/'.$certificate->id.'/download';

        $this->notificationService->send(
            $certificate->user_id,
            NotificationType::CertificateIssued,
            [
                'course_title' => $certificate->course->title,
                'user_name' => $certificate->user->full_name,
                'download_url' => $downloadUrl,
                'verification_code' => $certificate->verification_code,
                'issued_date' => $certificate->issued_at->toFormattedDateString(),
            ],
        );
    }
}
