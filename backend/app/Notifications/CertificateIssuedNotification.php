<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Certificate $certificate,
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
        $courseTitle = (string) ($this->certificate->metadata['course_title'] ?? $this->certificate->course?->title ?? 'your course');

        return (new MailMessage)
            ->subject('Your course certificate is ready')
            ->greeting("Hello {$notifiable->full_name},")
            ->line("You have earned a certificate for {$courseTitle}.")
            ->line('Your certificate is now available in the Securecy LMS learner portal.')
            ->line('Verification code: '.$this->certificate->verification_code);
    }
}
