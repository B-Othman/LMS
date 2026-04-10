<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\CourseCompleted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCourseCompletedNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->loadMissing('user', 'course');

        if (! $enrollment->user || ! $enrollment->course) {
            return;
        }

        $this->notificationService->send(
            $enrollment->user_id,
            NotificationType::CourseCompleted,
            [
                'course_title' => $enrollment->course->title,
                'user_name' => $enrollment->user->full_name,
                'completed_date' => now()->toFormattedDateString(),
            ],
        );
    }
}
