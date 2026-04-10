<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Jobs\SendEmailNotificationJob;
use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Send a notification to a user.
     *
     * Resolves the template (tenant override → system default), checks user preferences,
     * renders the subject/body, and dispatches to the appropriate channel(s).
     *
     * @param  array<string, string>  $data  Placeholder values for template interpolation
     */
    public function send(int $userId, NotificationType $type, array $data = []): void
    {
        $user = User::withoutGlobalScopes()->find($userId);

        if (! $user) {
            return;
        }

        $template = $this->resolveTemplate($user->tenant_id, $type);

        if (! $template || ! $template->is_active) {
            return;
        }

        $preference = $this->getPreference($userId, $type);

        $subject = $template->renderSubject($data);
        $bodyHtml = $template->renderBodyHtml($data);
        $bodyText = $template->renderBodyText($data);

        $channel = $template->channel;

        $sendEmail = $channel === NotificationChannel::Email || $channel === NotificationChannel::Both;
        $sendInApp = $channel === NotificationChannel::InApp || $channel === NotificationChannel::Both;

        if ($preference) {
            $sendEmail = $sendEmail && $preference->email_enabled;
            $sendInApp = $sendInApp && $preference->in_app_enabled;
        }

        if ($sendInApp) {
            $this->createInAppNotification($user, $type, $subject, $bodyHtml, $bodyText, $data);
        }

        if ($sendEmail) {
            $this->dispatchEmail($user, $type, $subject, $bodyHtml, $bodyText, $data);
        }
    }

    private function resolveTemplate(?int $tenantId, NotificationType $type): ?NotificationTemplate
    {
        // Try tenant override first, then fall back to system default
        if ($tenantId !== null) {
            $tenant = NotificationTemplate::where('tenant_id', $tenantId)
                ->where('type', $type->value)
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        return NotificationTemplate::whereNull('tenant_id')
            ->where('type', $type->value)
            ->first();
    }

    private function getPreference(int $userId, NotificationType $type): ?NotificationPreference
    {
        return NotificationPreference::where('user_id', $userId)
            ->where('type', $type->value)
            ->first();
    }

    /**
     * @param  array<string, string>  $data
     */
    private function createInAppNotification(
        User $user,
        NotificationType $type,
        string $subject,
        string $bodyHtml,
        string $bodyText,
        array $data,
    ): void {
        AppNotification::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'type' => $type->value,
            'channel' => NotificationChannel::InApp->value,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'data' => $data,
            'status' => NotificationStatus::Sent->value,
            'sent_at' => now(),
        ]);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function dispatchEmail(
        User $user,
        NotificationType $type,
        string $subject,
        string $bodyHtml,
        string $bodyText,
        array $data,
    ): void {
        $notification = AppNotification::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'type' => $type->value,
            'channel' => NotificationChannel::Email->value,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'data' => $data,
            'status' => NotificationStatus::Pending->value,
        ]);

        DB::afterCommit(fn () => SendEmailNotificationJob::dispatch($notification->id)->afterCommit());
    }

    /**
     * Get all notification types with their user preference state.
     *
     * @return array<int, array{type: string, label: string, email_enabled: bool, in_app_enabled: bool}>
     */
    public function getUserPreferences(int $userId): array
    {
        $preferences = NotificationPreference::where('user_id', $userId)
            ->get()
            ->keyBy('type');

        return collect(NotificationType::cases())->map(function (NotificationType $type) use ($preferences) {
            $pref = $preferences->get($type->value);

            return [
                'type' => $type->value,
                'label' => $type->label(),
                'email_enabled' => $pref ? $pref->email_enabled : true,
                'in_app_enabled' => $pref ? $pref->in_app_enabled : true,
            ];
        })->values()->all();
    }

    /**
     * Bulk update user preferences.
     *
     * @param  array<int, array{type: string, email_enabled: bool, in_app_enabled: bool}>  $preferences
     */
    public function updateUserPreferences(int $userId, array $preferences): void
    {
        foreach ($preferences as $pref) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'type' => $pref['type']],
                ['email_enabled' => $pref['email_enabled'], 'in_app_enabled' => $pref['in_app_enabled']],
            );
        }
    }
}
