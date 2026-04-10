export type NotificationChannel = "email" | "in_app" | "both";
export type NotificationStatus = "pending" | "sent" | "failed" | "read";
export type NotificationType =
  | "enrollment_created"
  | "course_completed"
  | "certificate_issued"
  | "quiz_failed"
  | "welcome"
  | "enrollment_reminder"
  | "course_due_soon";

export interface AppNotification {
  id: number;
  type: NotificationType;
  channel: NotificationChannel;
  subject: string;
  body_html: string;
  body_text: string | null;
  data: Record<string, string>;
  status: NotificationStatus;
  is_read: boolean;
  sent_at: string | null;
  read_at: string | null;
  created_at: string;
}

export interface NotificationPreference {
  type: NotificationType;
  label: string;
  email_enabled: boolean;
  in_app_enabled: boolean;
}

export interface NotificationTemplate {
  id: number;
  tenant_id: number | null;
  type: NotificationType;
  subject_template: string;
  body_html_template: string;
  body_text_template: string;
  channel: NotificationChannel;
  is_active: boolean;
  is_tenant_override: boolean;
  created_at: string;
  updated_at: string;
}

export interface UpdateNotificationTemplatePayload {
  subject_template: string;
  body_html_template: string;
  body_text_template: string;
  channel: NotificationChannel;
  is_active: boolean;
}

export interface UpdateNotificationPreferencesPayload {
  preferences: Array<{
    type: string;
    email_enabled: boolean;
    in_app_enabled: boolean;
  }>;
}
