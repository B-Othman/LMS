<?php

namespace App\Listeners;

use App\Events\EnrollmentCreated;
use App\Notifications\EnrollmentAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnrollmentCreatedNotification implements ShouldQueue
{
    public function handle(EnrollmentCreated $event): void
    {
        $enrollment = $event->enrollment->loadMissing('user', 'course');

        if (! $enrollment->user || ! $enrollment->course) {
            return;
        }

        $enrollment->user->notify(new EnrollmentAssignedNotification($enrollment));
    }
}
