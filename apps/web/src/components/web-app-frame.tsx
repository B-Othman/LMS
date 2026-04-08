"use client";

import type { ReactNode } from "react";

import {
  Alert,
  AppShell,
  CertificatesIcon,
  CoursesIcon,
  DashboardIcon,
  ProtectedRoute,
  useAuth,
  type NavigationItem,
} from "@securecy/ui";
import { usePathname } from "next/navigation";

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
];

export function WebAppFrame({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const {
    user,
    logout,
    forbiddenMessage,
    clearForbiddenMessage,
    hasAnyPermission,
  } = useAuth();

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
    pathname.startsWith("/reset-password")
  );
}
