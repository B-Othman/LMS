"use client";

import type { ReactNode } from "react";

import { AuthProvider, ToastProvider } from "@securecy/ui";

import { api, tenantAuthPayload } from "@/lib/api";

export function Providers({ children }: { children: ReactNode }) {
  return (
    <AuthProvider api={api} tenantAuthPayload={tenantAuthPayload}>
      <ToastProvider>{children}</ToastProvider>
    </AuthProvider>
  );
}
