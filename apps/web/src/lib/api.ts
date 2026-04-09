import { createApiClient } from "@securecy/config/api-client";
import {
  FORBIDDEN_EVENT_NAME,
  UNAUTHORIZED_EVENT_NAME,
  clearStoredToken,
  getStoredToken,
} from "@securecy/config/auth-storage";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";
const rawTenantId = process.env.NEXT_PUBLIC_TENANT_ID;
const parsedTenantId = rawTenantId ? Number(rawTenantId) : Number.NaN;
const tenantSlug = process.env.NEXT_PUBLIC_TENANT_SLUG ?? "securecy";

export const tenantAuthPayload = Number.isFinite(parsedTenantId)
  ? { tenant_id: parsedTenantId }
  : { tenant_slug: tenantSlug };

export const api = createApiClient({
  baseUrl: API_BASE_URL,
  getAccessToken: getStoredToken,
  onUnauthorized: () => {
    clearStoredToken();

    if (typeof window === "undefined") {
      return;
    }

    window.dispatchEvent(new Event(UNAUTHORIZED_EVENT_NAME));

    if (isPublicPath(window.location.pathname)) {
      return;
    }

    const next = `${window.location.pathname}${window.location.search}`;
    window.location.assign(`/login?next=${encodeURIComponent(next)}`);
  },
  onForbidden: (error) => {
    if (typeof window === "undefined") {
      return;
    }

    window.dispatchEvent(
      new CustomEvent(FORBIDDEN_EVENT_NAME, {
        detail: { message: error.message },
      }),
    );
  },
});

function isPublicPath(pathname: string): boolean {
  return (
    pathname === "/login" ||
    pathname === "/forgot-password" ||
    pathname.startsWith("/reset-password")
  );
}
