<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Enrollment $enrollment,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('A new course has been assigned to you')
            ->greeting("Hello {$notifiable->full_name},")
            ->line("You have been enrolled in {$this->enrollment->course->title}.")
            ->line('Sign in to the Securecy LMS to start learning.');

        if ($this->enrollment->due_at) {
            $message->line('Due date: '.$this->enrollment->due_at->toFormattedDateString());
        }

        return $message;
    }
}
