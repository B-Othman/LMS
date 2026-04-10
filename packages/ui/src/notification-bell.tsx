"use client";

import { useEffect, useRef, useState } from "react";
import type { AppNotification } from "@securecy/types";
import { AwardIcon, BellIcon, BookOpenIcon, CheckCircleIcon, ClockIcon, XCircleIcon } from "./icons";

interface NotificationBellProps {
  fetchUnreadCount: () => Promise<number>;
  fetchNotifications: () => Promise<AppNotification[]>;
  markRead: (id: number) => Promise<void>;
  markAllRead: () => Promise<void>;
  onViewAll: () => void;
  onNavigate?: (notification: AppNotification) => void;
  pollIntervalMs?: number;
}

function getTypeIcon(type: AppNotification["type"]) {
  switch (type) {
    case "enrollment_created":
      return <BookOpenIcon className="h-4 w-4 text-primary-600" />;
    case "course_completed":
      return <CheckCircleIcon className="h-4 w-4 text-success-600" />;
    case "certificate_issued":
      return <AwardIcon className="h-4 w-4 text-warning-600" />;
    case "quiz_failed":
      return <XCircleIcon className="h-4 w-4 text-error-600" />;
    case "course_due_soon":
    case "enrollment_reminder":
      return <ClockIcon className="h-4 w-4 text-warning-600" />;
    default:
      return <BellIcon className="h-4 w-4 text-primary-600" />;
  }
}

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime();
  const minutes = Math.floor(diff / 60_000);
  if (minutes < 1) return "just now";
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

export function NotificationBell({
  fetchUnreadCount,
  fetchNotifications,
  markRead,
  markAllRead,
  onViewAll,
  onNavigate,
  pollIntervalMs = 60_000,
}: NotificationBellProps) {
  const [open, setOpen] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [notifications, setNotifications] = useState<AppNotification[]>([]);
  const [loading, setLoading] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  // Poll unread count
  useEffect(() => {
    let cancelled = false;

    async function poll() {
      try {
        const count = await fetchUnreadCount();
        if (!cancelled) setUnreadCount(count);
      } catch {
        // silently ignore
      }
    }

    void poll();
    const id = setInterval(() => void poll(), pollIntervalMs);
    return () => {
      cancelled = true;
      clearInterval(id);
    };
  }, [fetchUnreadCount, pollIntervalMs]);

  // Load notifications when opening
  useEffect(() => {
    if (!open) return;

    setLoading(true);
    fetchNotifications()
      .then((items) => setNotifications(items))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [open, fetchNotifications]);

  // Close on outside click
  useEffect(() => {
    function handler(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  async function handleMarkRead(n: AppNotification) {
    if (!n.is_read) {
      await markRead(n.id);
      setNotifications((prev) =>
        prev.map((item) =>
          item.id === n.id ? { ...item, is_read: true, status: "read" as const } : item,
        ),
      );
      setUnreadCount((c) => Math.max(0, c - 1));
    }
    if (onNavigate) onNavigate(n);
  }

  async function handleMarkAllRead() {
    await markAllRead();
    setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true, status: "read" as const })));
    setUnreadCount(0);
  }

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="relative flex items-center justify-center rounded-lg border border-primary-100 bg-white p-2 text-primary-700 transition-colors hover:bg-primary-50"
        aria-label={`Notifications${unreadCount > 0 ? `, ${unreadCount} unread` : ""}`}
      >
        <BellIcon className="h-5 w-5" />
        {unreadCount > 0 ? (
          <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-error-500 px-1 text-[10px] font-bold text-white">
            {unreadCount > 99 ? "99+" : unreadCount}
          </span>
        ) : null}
      </button>

      {open ? (
        <div className="absolute right-0 z-50 mt-2 w-96 rounded-card border border-neutral-200 bg-white shadow-card">
          <div className="flex items-center justify-between border-b border-neutral-100 px-4 py-3">
            <p className="text-body-md font-semibold text-night-900">Notifications</p>
            {unreadCount > 0 ? (
              <button
                type="button"
                onClick={() => void handleMarkAllRead()}
                className="text-body-sm font-medium text-primary-600 hover:underline"
              >
                Mark all as read
              </button>
            ) : null}
          </div>

          <div className="max-h-96 overflow-y-auto">
            {loading ? (
              <div className="py-8 text-center text-body-sm text-neutral-400">Loading…</div>
            ) : notifications.length === 0 ? (
              <div className="py-8 text-center text-body-sm text-neutral-400">No notifications yet</div>
            ) : (
              notifications.slice(0, 8).map((n) => (
                <button
                  key={n.id}
                  type="button"
                  onClick={() => void handleMarkRead(n)}
                  className={`flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-primary-50 ${
                    !n.is_read ? "bg-primary-50/50" : ""
                  }`}
                >
                  <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-neutral-100">
                    {getTypeIcon(n.type)}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className={`text-body-sm ${!n.is_read ? "font-semibold text-night-900" : "text-night-700"} truncate`}>
                      {n.subject}
                    </p>
                    <p className="mt-0.5 text-body-sm text-neutral-500">{timeAgo(n.created_at)}</p>
                  </div>
                  {!n.is_read ? (
                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary-500" />
                  ) : null}
                </button>
              ))
            )}
          </div>

          <div className="border-t border-neutral-100 px-4 py-3">
            <button
              type="button"
              onClick={() => {
                setOpen(false);
                onViewAll();
              }}
              className="text-body-sm font-medium text-primary-600 hover:underline"
            >
              View all notifications
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
