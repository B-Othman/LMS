<?php

namespace App\Listeners;

use App\Events\CertificateIssued;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCertificateIssuedNotification implements ShouldQueue
{
    public function handle(CertificateIssued $event): void
    {
        $certificate = $event->certificate->loadMissing('user', 'course');

        if (! $certificate->user) {
            return;
        }

        $certificate->user->notify(new CertificateIssuedNotification($certificate));
    }
}
