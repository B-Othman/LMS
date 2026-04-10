<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\QuizCompleted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendQuizFailedNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(QuizCompleted $event): void
    {
        $attempt = $event->attempt;

        // Only notify on failure
        if ($attempt->passed) {
            return;
        }

        $attempt->loadMissing('quiz');

        if (! $attempt->quiz) {
            return;
        }

        $this->notificationService->send(
            $attempt->user_id,
            NotificationType::QuizFailed,
            [
                'quiz_title' => $attempt->quiz->title,
                'score' => (string) $attempt->score,
                'pass_score' => (string) $attempt->quiz->pass_score,
                'user_id' => (string) $attempt->user_id,
            ],
        );
    }
}
