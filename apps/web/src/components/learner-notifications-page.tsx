"use client";

import type { AppNotification } from "@securecy/types";
import { useCallback, useEffect, useState } from "react";
import {
  AwardIcon,
  BellIcon,
  BookOpenIcon,
  Button,
  CheckCircleIcon,
  ClockIcon,
  EmptyState,
  useToast,
  XCircleIcon,
} from "@securecy/ui";
import {
  fetchMyNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/notifications";

function getTypeIcon(type: AppNotification["type"]) {
  switch (type) {
    case "enrollment_created":
      return <BookOpenIcon className="h-5 w-5 text-primary-600" />;
    case "course_completed":
      return <CheckCircleIcon className="h-5 w-5 text-success-600" />;
    case "certificate_issued":
      return <AwardIcon className="h-5 w-5 text-warning-600" />;
    case "quiz_failed":
      return <XCircleIcon className="h-5 w-5 text-error-600" />;
    case "course_due_soon":
    case "enrollment_reminder":
      return <ClockIcon className="h-5 w-5 text-warning-600" />;
    default:
      return <BellIcon className="h-5 w-5 text-primary-600" />;
  }
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

type Filter = "all" | "unread";

export function LearnerNotificationsPage() {
  const { showToast } = useToast();
  const [filter, setFilter] = useState<Filter>("all");
  const [notifications, setNotifications] = useState<AppNotification[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isMarkingAll, setIsMarkingAll] = useState(false);

  const load = useCallback(async () => {
    setIsLoading(true);
    try {
      const result = await fetchMyNotifications(filter === "unread" ? "unread" : undefined);
      setNotifications(result.data ?? []);
    } catch {
      showToast({ tone: "error", message: "Failed to load notifications." });
    } finally {
      setIsLoading(false);
    }
  }, [filter, showToast]);

  useEffect(() => {
    void load();
  }, [load]);

  async function handleMarkRead(id: number) {
    try {
      await markNotificationRead(id);
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, is_read: true, status: "read" as const } : n)),
      );
    } catch {
      showToast({ tone: "error", message: "Failed to mark notification as read." });
    }
  }

  async function handleMarkAllRead() {
    setIsMarkingAll(true);
    try {
      await markAllNotificationsRead();
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true, status: "read" as const })));
      showToast({ tone: "success", message: "All notifications marked as read." });
    } catch {
      showToast({ tone: "error", message: "Failed to mark all as read." });
    } finally {
      setIsMarkingAll(false);
    }
  }

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-h2 font-bold text-night-900">Notifications</h1>
          {unreadCount > 0 ? (
            <p className="mt-1 text-body-sm text-neutral-500">{unreadCount} unread</p>
          ) : null}
        </div>
        {unreadCount > 0 ? (
          <Button
            variant="secondary"
            size="sm"
            onClick={() => void handleMarkAllRead()}
            disabled={isMarkingAll}
          >
            Mark all as read
          </Button>
        ) : null}
      </div>

      {/* Filter tabs */}
      <div className="flex gap-1 rounded-lg border border-neutral-200 bg-neutral-50 p-1">
        {(["all", "unread"] as Filter[]).map((f) => (
          <button
            key={f}
            type="button"
            onClick={() => setFilter(f)}
            className={`flex-1 rounded-md px-4 py-2 text-body-sm font-medium transition-colors capitalize ${
              filter === f
                ? "bg-white text-night-900 shadow-sm"
                : "text-neutral-500 hover:text-night-900"
            }`}
          >
            {f}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="space-y-3">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-20 animate-pulse rounded-card bg-neutral-100" />
          ))}
        </div>
      ) : notifications.length === 0 ? (
        <EmptyState
          title={filter === "unread" ? "No unread notifications" : "No notifications yet"}
          description={
            filter === "unread"
              ? "You're all caught up!"
              : "Notifications about your courses, certificates, and more will appear here."
          }
        />
      ) : (
        <div className="divide-y divide-neutral-100 overflow-hidden rounded-card border border-neutral-200 bg-white">
          {notifications.map((n) => (
            <div
              key={n.id}
              className={`flex items-start gap-4 px-5 py-4 transition-colors ${
                !n.is_read ? "bg-primary-50/40" : ""
              }`}
            >
              <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-100">
                {getTypeIcon(n.type)}
              </span>

              <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-2">
                  <p className={`text-body-md ${!n.is_read ? "font-semibold text-night-900" : "text-night-700"}`}>
                    {n.subject}
                  </p>
                  {!n.is_read ? (
                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary-500" />
                  ) : null}
                </div>
                <div
                  className="mt-1 text-body-sm text-neutral-600 [&_a]:text-primary-600 [&_a]:underline"
                  dangerouslySetInnerHTML={{ __html: n.body_html }}
                />
                <div className="mt-2 flex items-center gap-4">
                  <span className="text-body-sm text-neutral-400">{formatDate(n.created_at)}</span>
                  {!n.is_read ? (
                    <button
                      type="button"
                      onClick={() => void handleMarkRead(n.id)}
                      className="text-body-sm font-medium text-primary-600 hover:underline"
                    >
                      Mark as read
                    </button>
                  ) : null}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
