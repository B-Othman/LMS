import type { AppNotification, NotificationTemplate, PaginatedResponse, UpdateNotificationTemplatePayload } from "@securecy/types";

import { api } from "./api";

export async function fetchAdminMyNotificationsUnreadCount(): Promise<number> {
  const response = await api.get<{ count: number }>("/my/notifications/unread-count");
  return response.data?.count ?? 0;
}

export async function fetchAdminMyNotifications(): Promise<AppNotification[]> {
  const response = await api.paginated<AppNotification>("/my/notifications?per_page=20");
  return response.data ?? [];
}

export async function adminMarkNotificationRead(id: number): Promise<void> {
  await api.post(`/my/notifications/${id}/read`);
}

export async function adminMarkAllNotificationsRead(): Promise<void> {
  await api.post("/my/notifications/read-all");
}

export async function fetchNotificationTemplates(): Promise<NotificationTemplate[]> {
  const response = await api.get<NotificationTemplate[]>("/notification-templates");
  return response.data ?? [];
}

export async function updateNotificationTemplate(
  id: number,
  payload: UpdateNotificationTemplatePayload,
): Promise<NotificationTemplate> {
  const response = await api.put<NotificationTemplate>(`/notification-templates/${id}`, payload);

  if (!response.data) {
    throw new Error("Failed to update template.");
  }

  return response.data;
}

export async function resetNotificationTemplate(id: number): Promise<NotificationTemplate> {
  const response = await api.post<NotificationTemplate>(`/notification-templates/${id}/reset`);

  if (!response.data) {
    throw new Error("Failed to reset template.");
  }

  return response.data;
}
