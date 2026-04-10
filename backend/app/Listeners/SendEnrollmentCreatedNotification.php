<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\EnrollmentCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnrollmentCreatedNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(EnrollmentCreated $event): void
    {
        $enrollment = $event->enrollment->loadMissing('user', 'course');

        if (! $enrollment->user || ! $enrollment->course) {
            return;
        }

        $data = [
            'course_title' => $enrollment->course->title,
            'user_name' => $enrollment->user->full_name,
            'due_date' => $enrollment->due_at?->toFormattedDateString() ?? 'No due date',
            'login_url' => config('app.frontend_url', 'http://localhost:3000').'/login',
        ];

        $this->notificationService->send(
            $enrollment->user_id,
            NotificationType::EnrollmentCreated,
            $data,
        );
    }
}
