<?php

namespace App\Providers;

use App\Events\CertificateIssued;
use App\Events\CourseCompleted;
use App\Events\EnrollmentCreated;
use App\Events\QuizCompleted;
use App\Listeners\QueueCourseCertificateEligibilityCheck;
use App\Listeners\SendCertificateIssuedNotification;
use App\Listeners\SendCourseCompletedNotification;
use App\Listeners\SendEnrollmentCreatedNotification;
use App\Listeners\SendQuizFailedNotification;
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
            SendCourseCompletedNotification::class,
        ],
        CertificateIssued::class => [
            SendCertificateIssuedNotification::class,
        ],
        QuizCompleted::class => [
            SendQuizFailedNotification::class,
        ],
    ];
}
