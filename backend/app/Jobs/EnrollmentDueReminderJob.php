<?php

namespace App\Jobs;

use App\Enums\EnrollmentStatus;
use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrollmentDueReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        // Find active enrollments due within 3 days that haven't had a reminder sent
        Enrollment::withoutGlobalScopes()
            ->where('status', EnrollmentStatus::Active->value)
            ->whereNotNull('due_at')
            ->whereNull('reminder_sent_at')
            ->whereBetween('due_at', [now(), now()->addDays(3)])
            ->with(['user', 'course'])
            ->chunkById(100, function ($enrollments) use ($notificationService) {
                foreach ($enrollments as $enrollment) {
                    if (! $enrollment->user || ! $enrollment->course) {
                        continue;
                    }

                    $notificationService->send(
                        $enrollment->user_id,
                        NotificationType::CourseDueSoon,
                        [
                            'course_title' => $enrollment->course->title,
                            'due_date' => $enrollment->due_at->toFormattedDateString(),
                            'days_remaining' => (string) now()->diffInDays($enrollment->due_at, false),
                        ],
                    );

                    $enrollment->update(['reminder_sent_at' => now()]);
                }
            });
    }
}
