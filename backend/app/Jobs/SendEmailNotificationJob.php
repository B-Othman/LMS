<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Mail\NotificationMail;
use App\Models\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $notificationId,
    ) {}

    public function handle(): void
    {
        $notification = AppNotification::with('user')->find($this->notificationId);

        if (! $notification || ! $notification->user) {
            return;
        }

        Mail::to($notification->user->email)->send(new NotificationMail(
            $notification->subject,
            $notification->body_html,
            $notification->body_text ?? strip_tags($notification->body_html),
        ));

        $notification->update([
            'status' => NotificationStatus::Sent->value,
            'sent_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        AppNotification::where('id', $this->notificationId)->update([
            'status' => NotificationStatus::Failed->value,
            'failed_reason' => $e->getMessage(),
        ]);
    }
}
