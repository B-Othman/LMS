"use client";

import { useRouter } from "next/navigation";
import { useEffect, type ReactNode } from "react";

import { EmptyState } from "./empty-state";
import { useAuth } from "./auth-provider";

interface ProtectedRouteProps {
  children: ReactNode;
  redirectTo?: string;
  requiredRoles?: string[];
  requiredPermissions?: string[];
  permissionMode?: "any" | "all";
  loadingFallback?: ReactNode;
  unauthorizedFallback?: ReactNode;
}

export function ProtectedRoute({
  children,
  redirectTo = "/login",
  requiredRoles,
  requiredPermissions,
  permissionMode = "any",
  loadingFallback,
  unauthorizedFallback,
}: ProtectedRouteProps) {
  const router = useRouter();
  const { isAuthenticated, isLoading, hasRole, hasPermission } = useAuth();

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.replace(redirectTo);
    }
  }, [isAuthenticated, isLoading, redirectTo, router]);

  if (isLoading) {
    return (
      <>
        {loadingFallback ?? (
          <div className="flex min-h-[50vh] items-center justify-center px-6 py-12">
            <p className="text-body-lg text-neutral-500">Loading your workspace...</p>
          </div>
        )}
      </>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  const satisfiesRole = !requiredRoles || requiredRoles.some((role) => hasRole(role));
  const satisfiesPermission =
    !requiredPermissions ||
    (permissionMode === "all"
      ? requiredPermissions.every((permission) => hasPermission(permission))
      : requiredPermissions.some((permission) => hasPermission(permission)));

  if (!satisfiesRole || !satisfiesPermission) {
    return (
      <>
        {unauthorizedFallback ?? (
          <div className="mx-auto max-w-4xl px-6 py-10">
            <EmptyState
              title="Access Denied"
              description="Your account does not have the permissions required to view this page."
            />
          </div>
        )}
      </>
    );
  }

  return <>{children}</>;
}
