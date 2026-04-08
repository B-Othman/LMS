"use client";

import type { ReactNode } from "react";

import {
  Alert,
  AppShell,
  CoursesIcon,
  DashboardIcon,
  EmptyState,
  EnrollmentsIcon,
  ProtectedRoute,
  ReportsIcon,
  SettingsIcon,
  UsersIcon,
  useAuth,
  type NavigationItem,
} from "@securecy/ui";
import { usePathname } from "next/navigation";

const adminRoles = ["system_admin", "tenant_admin", "content_manager", "instructor"];

const navigationConfig: Array<NavigationItem & { requiredPermissions?: string[] }> = [
  { href: "/dashboard", label: "Dashboard", icon: <DashboardIcon className="h-5 w-5" /> },
  {
    href: "/users",
    label: "Users",
    icon: <UsersIcon className="h-5 w-5" />,
    requiredPermissions: ["users.view"],
  },
  {
    href: "/courses",
    label: "Courses",
    icon: <CoursesIcon className="h-5 w-5" />,
    requiredPermissions: ["courses.view", "courses.create", "courses.update", "courses.publish"],
  },
  {
    href: "/enrollments",
    label: "Enrollments",
    icon: <EnrollmentsIcon className="h-5 w-5" />,
    requiredPermissions: ["enrollments.view"],
  },
  {
    href: "/reports",
    label: "Reports",
    icon: <ReportsIcon className="h-5 w-5" />,
    requiredPermissions: ["reports.view"],
  },
  {
    href: "/settings",
    label: "Settings",
    icon: <SettingsIcon className="h-5 w-5" />,
    requiredPermissions: ["settings.manage"],
  },
];

export function AdminAppFrame({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const {
    user,
    logout,
    forbiddenMessage,
    clearForbiddenMessage,
    hasAnyPermission,
  } = useAuth();

  if (pathname === "/login") {
    return <>{children}</>;
  }

  const navigation = navigationConfig.filter((item) => {
    return !item.requiredPermissions || hasAnyPermission(item.requiredPermissions);
  });

  const userName = [user?.first_name, user?.last_name].filter(Boolean).join(" ") || "Admin User";

  return (
    <ProtectedRoute
      redirectTo="/login"
      requiredRoles={adminRoles}
      unauthorizedFallback={
        <div className="flex min-h-screen items-center justify-center bg-primary-50 px-4">
          <div className="w-full max-w-xl">
            <EmptyState
              title="Access Denied"
              description="This portal is available only to tenant admins, system admins, instructors, and content managers."
            />
          </div>
        </div>
      }
    >
      <AppShell
        brand="Admin Portal"
        navigation={navigation}
        userName={userName}
        userEmail={user?.email ?? ""}
        onLogout={logout}
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
