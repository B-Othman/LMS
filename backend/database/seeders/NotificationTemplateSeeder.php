<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => 'enrollment_created',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => 'You have been enrolled in {{course_title}}',
                'body_html_template' => $this->enrollmentCreatedHtml(),
                'body_text_template' => "Hello {{user_name}},\n\nYou have been enrolled in {{course_title}}.\n\nDue date: {{due_date}}\n\nSign in to start learning: {{login_url}}",
            ],
            [
                'type' => 'course_completed',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => 'Congratulations! You completed {{course_title}}',
                'body_html_template' => $this->courseCompletedHtml(),
                'body_text_template' => "Hello {{user_name}},\n\nCongratulations! You have successfully completed {{course_title}} on {{completed_date}}.\n\nKeep up the great work!",
            ],
            [
                'type' => 'certificate_issued',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => 'Your certificate for {{course_title}} is ready',
                'body_html_template' => $this->certificateIssuedHtml(),
                'body_text_template' => "Hello {{user_name}},\n\nYour certificate for {{course_title}} has been issued on {{issued_date}}.\n\nVerification code: {{verification_code}}\n\nDownload your certificate: {{download_url}}",
            ],
            [
                'type' => 'quiz_failed',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => 'Quiz result: {{quiz_title}}',
                'body_html_template' => $this->quizFailedHtml(),
                'body_text_template' => "Hello,\n\nYou did not pass {{quiz_title}}.\n\nYour score: {{score}}%\nPassing score: {{pass_score}}%\n\nDon't give up — you can retake the quiz when available.",
            ],
            [
                'type' => 'welcome',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => 'Welcome to Securecy LMS',
                'body_html_template' => $this->welcomeHtml(),
                'body_text_template' => "Hello {{user_name}},\n\nWelcome to Securecy LMS! Your account has been created.\n\nSign in here: {{login_url}}",
            ],
            [
                'type' => 'enrollment_reminder',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => 'Reminder: {{course_title}} is due soon',
                'body_html_template' => $this->enrollmentReminderHtml(),
                'body_text_template' => "Hello,\n\nThis is a reminder that {{course_title}} is due on {{due_date}} ({{days_remaining}} days remaining).\n\nSign in to continue learning.",
            ],
            [
                'type' => 'course_due_soon',
                'channel' => NotificationChannel::Both->value,
                'subject_template' => '{{course_title}} is due in {{days_remaining}} days',
                'body_html_template' => $this->courseDueSoonHtml(),
                'body_text_template' => "Hello,\n\n{{course_title}} is due on {{due_date}} — just {{days_remaining}} days away.\n\nSign in now to complete your course.",
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['tenant_id' => null, 'type' => $template['type']],
                $template,
            );
        }
    }

    private function enrollmentCreatedHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:16px;">You have a new course assigned</p>
<p style="color:#4a5568;margin-bottom:12px;">Hello <strong>{{user_name}}</strong>,</p>
<p style="color:#4a5568;margin-bottom:12px;">You have been enrolled in <strong>{{course_title}}</strong>.</p>
<p style="color:#4a5568;margin-bottom:20px;">Due date: <strong>{{due_date}}</strong></p>
<a href="{{login_url}}" class="button" style="display:inline-block;padding:12px 28px;background-color:#3b7ab8;color:#ffffff;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Start Learning</a>
HTML;
    }

    private function courseCompletedHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:16px;">Course Completed!</p>
<p style="color:#4a5568;margin-bottom:12px;">Hello <strong>{{user_name}}</strong>,</p>
<p style="color:#4a5568;margin-bottom:12px;">Congratulations! You have successfully completed <strong>{{course_title}}</strong> on {{completed_date}}.</p>
<p style="color:#4a5568;">Keep up the great work and continue building your skills!</p>
HTML;
    }

    private function certificateIssuedHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:16px;">Your certificate is ready</p>
<p style="color:#4a5568;margin-bottom:12px;">Hello <strong>{{user_name}}</strong>,</p>
<p style="color:#4a5568;margin-bottom:12px;">Your certificate for <strong>{{course_title}}</strong> has been issued on {{issued_date}}.</p>
<p style="color:#4a5568;margin-bottom:20px;">Verification code: <strong>{{verification_code}}</strong></p>
<a href="{{download_url}}" class="button" style="display:inline-block;padding:12px 28px;background-color:#3b7ab8;color:#ffffff;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Download Certificate</a>
HTML;
    }

    private function quizFailedHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:16px;">Quiz Result</p>
<p style="color:#4a5568;margin-bottom:12px;">You did not pass <strong>{{quiz_title}}</strong> this time.</p>
<table style="border-collapse:collapse;margin-bottom:20px;">
  <tr><td style="padding:4px 12px 4px 0;color:#6b7a99;">Your score</td><td style="font-weight:700;color:#e53e3e;">{{score}}%</td></tr>
  <tr><td style="padding:4px 12px 4px 0;color:#6b7a99;">Passing score</td><td style="font-weight:700;color:#3b7ab8;">{{pass_score}}%</td></tr>
</table>
<p style="color:#4a5568;">Don't give up — review the material and try again when available.</p>
HTML;
    }

    private function welcomeHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:16px;">Welcome to Securecy LMS</p>
<p style="color:#4a5568;margin-bottom:12px;">Hello <strong>{{user_name}}</strong>,</p>
<p style="color:#4a5568;margin-bottom:20px;">Your account has been created. Sign in to get started with your learning journey.</p>
<a href="{{login_url}}" class="button" style="display:inline-block;padding:12px 28px;background-color:#3b7ab8;color:#ffffff;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Sign In</a>
HTML;
    }

    private function enrollmentReminderHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:16px;">Course Reminder</p>
<p style="color:#4a5568;margin-bottom:12px;"><strong>{{course_title}}</strong> is due on <strong>{{due_date}}</strong> — {{days_remaining}} days remaining.</p>
<p style="color:#4a5568;">Sign in now to continue your course and meet the deadline.</p>
HTML;
    }

    private function courseDueSoonHtml(): string
    {
        return <<<'HTML'
<p style="font-size:16px;font-weight:700;color:#c05621;margin-bottom:16px;">Course Due Soon</p>
<p style="color:#4a5568;margin-bottom:12px;"><strong>{{course_title}}</strong> is due on <strong>{{due_date}}</strong> — just {{days_remaining}} days away.</p>
<p style="color:#4a5568;margin-bottom:20px;">Don't miss the deadline — sign in now to complete your course.</p>
<a href="{{login_url}}" style="display:inline-block;padding:12px 28px;background-color:#3b7ab8;color:#ffffff;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Continue Learning</a>
HTML;
    }
}
