<?php

namespace App\Providers;

use App\Events\CourseCompleted;
use App\Events\EnrollmentCreated;
use App\Listeners\QueueCourseCertificateEligibilityCheck;
use App\Listeners\SendEnrollmentCreatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        EnrollmentCreated::class => [
            SendEnrollmentCreatedNotification::class,
        ],
        CourseCompleted::class => [
            QueueCourseCertificateEligibilityCheck::class,
        ],
    ];
}
