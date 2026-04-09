<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
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
        return (new MailMessage)
            ->subject('Welcome to Securecy LMS')
            ->greeting("Hello {$notifiable->full_name},")
            ->line("Your {$this->tenantName} account is ready.")
            ->line('You can sign in to the Securecy LMS using the credentials provided by your administrator.');
    }
}
