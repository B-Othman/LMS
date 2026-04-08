"use client";

import type { ReactNode } from "react";

import { AuthProvider } from "@securecy/ui";

import { api, tenantId } from "@/lib/api";

export function Providers({ children }: { children: ReactNode }) {
  return (
    <AuthProvider api={api} tenantId={tenantId}>
      {children}
    </AuthProvider>
  );
}
