<?php

namespace App\Enums;

enum NotificationType: string
{
    case EnrollmentCreated = 'enrollment_created';
    case CourseCompleted = 'course_completed';
    case CertificateIssued = 'certificate_issued';
    case QuizFailed = 'quiz_failed';
    case Welcome = 'welcome';
    case EnrollmentReminder = 'enrollment_reminder';
    case CourseDueSoon = 'course_due_soon';

    public function label(): string
    {
        return match ($this) {
            self::EnrollmentCreated => 'Course Enrollment',
            self::CourseCompleted => 'Course Completed',
            self::CertificateIssued => 'Certificate Issued',
            self::QuizFailed => 'Quiz Failed',
            self::Welcome => 'Welcome',
            self::EnrollmentReminder => 'Enrollment Reminder',
            self::CourseDueSoon => 'Course Due Soon',
        };
    }
}
