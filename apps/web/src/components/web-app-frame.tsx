"use client";

import type { AppNotification } from "@securecy/types";
import type { ReactNode } from "react";
import { useCallback } from "react";

import {
  Alert,
  AppShell,
  CertificatesIcon,
  CoursesIcon,
  DashboardIcon,
  NotificationBell,
  ProtectedRoute,
  SettingsIcon,
  useAuth,
  type NavigationItem,
} from "@securecy/ui";
import { usePathname, useRouter } from "next/navigation";
import {
  fetchMyNotifications,
  fetchMyNotificationsUnreadCount,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/notifications";

const navigationConfig: Array<NavigationItem & { requiredPermissions?: string[] }> = [
  { href: "/dashboard", label: "Dashboard", icon: <DashboardIcon className="h-5 w-5" /> },
  {
    href: "/courses",
    label: "My Courses",
    icon: <CoursesIcon className="h-5 w-5" />,
    requiredPermissions: ["courses.view"],
  },
  {
    href: "/certificates",
    label: "Certificates",
    icon: <CertificatesIcon className="h-5 w-5" />,
    requiredPermissions: ["certificates.view"],
  },
  { href: "/settings/notifications", label: "Notification Settings", icon: <SettingsIcon className="h-5 w-5" /> },
];

export function WebAppFrame({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const {
    user,
    logout,
    forbiddenMessage,
    clearForbiddenMessage,
    hasAnyPermission,
  } = useAuth();

  const getUnreadCount = useCallback(() => fetchMyNotificationsUnreadCount(), []);
  const getNotifications = useCallback(
    () => fetchMyNotifications().then((r) => r.data ?? []),
    [],
  );
  const doMarkRead = useCallback((id: number) => markNotificationRead(id), []);
  const doMarkAllRead = useCallback(() => markAllNotificationsRead(), []);

  function handleNotificationNavigate(n: AppNotification) {
    if (n.type === "enrollment_created" || n.type === "course_completed" || n.type === "course_due_soon") {
      router.push("/courses");
    } else if (n.type === "certificate_issued") {
      router.push("/certificates");
    }
  }

  if (isPublicPath(pathname)) {
    return <>{children}</>;
  }

  const navigation = navigationConfig.filter((item) => {
    return !item.requiredPermissions || hasAnyPermission(item.requiredPermissions);
  });

  const userName = [user?.first_name, user?.last_name].filter(Boolean).join(" ") || "Learner";

  return (
    <ProtectedRoute redirectTo="/login">
      <AppShell
        brand="Learning Portal"
        navigation={navigation}
        userName={userName}
        userEmail={user?.email ?? ""}
        onLogout={logout}
        topBarEnd={
          <NotificationBell
            fetchUnreadCount={getUnreadCount}
            fetchNotifications={getNotifications}
            markRead={doMarkRead}
            markAllRead={doMarkAllRead}
            onViewAll={() => router.push("/notifications")}
            onNavigate={handleNotificationNavigate}
          />
        }
        notice={
          forbiddenMessage ? (
            <Alert tone="error" title="Access Restricted">
              <div className="flex items-center justify-between gap-3">
                <span>{forbiddenMessage}</span>
                <button
                  type="button"
                  onClick={clearForbiddenMessage}
                  className="shrink-0 text-body-sm font-semibold text-error-700 underline"
                >
                  Dismiss
                </button>
              </div>
            </Alert>
          ) : undefined
        }
      >
        {children}
      </AppShell>
    </ProtectedRoute>
  );
}

function isPublicPath(pathname: string): boolean {
  return (
    pathname === "/login" ||
    pathname === "/forgot-password" ||
    pathname.startsWith("/reset-password") ||
    pathname.startsWith("/verify/")
  );
}
