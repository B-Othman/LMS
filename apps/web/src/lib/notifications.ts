import type {
  AppNotification,
  NotificationPreference,
  PaginatedResponse,
  UpdateNotificationPreferencesPayload,
} from "@securecy/types";

import { api } from "./api";

export async function fetchMyNotifications(filter?: "unread"): Promise<PaginatedResponse<AppNotification>> {
  const params = new URLSearchParams({ per_page: "20" });
  if (filter) params.set("filter", filter);

  const response = await api.paginated<AppNotification>(`/my/notifications?${params.toString()}`);
  return response;
}

export async function fetchMyNotificationsUnreadCount(): Promise<number> {
  const response = await api.get<{ count: number }>("/my/notifications/unread-count");
  return response.data?.count ?? 0;
}

export async function markNotificationRead(id: number): Promise<void> {
  await api.post(`/my/notifications/${id}/read`);
}

export async function markAllNotificationsRead(): Promise<void> {
  await api.post("/my/notifications/read-all");
}

export async function fetchMyNotificationPreferences(): Promise<NotificationPreference[]> {
  const response = await api.get<NotificationPreference[]>("/my/notification-preferences");
  return response.data ?? [];
}

export async function updateMyNotificationPreferences(
  payload: UpdateNotificationPreferencesPayload,
): Promise<NotificationPreference[]> {
  const response = await api.put<NotificationPreference[]>("/my/notification-preferences", payload);
  return response.data ?? [];
}
