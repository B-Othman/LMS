<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class QueueCourseCertificateEligibilityCheck implements ShouldQueue
{
    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->loadMissing('course', 'user');

        if (! $enrollment->course?->certificate_template_id) {
            return;
        }

        Log::info('Course completed; certificate generation placeholder queued.', [
            'enrollment_id' => $enrollment->id,
            'course_id' => $enrollment->course_id,
            'user_id' => $enrollment->user_id,
            'certificate_template_id' => $enrollment->course->certificate_template_id,
        ]);
    }
}
