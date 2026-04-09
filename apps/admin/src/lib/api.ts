import { ApiClientError, createApiClient } from "@securecy/config/api-client";
import {
  FORBIDDEN_EVENT_NAME,
  UNAUTHORIZED_EVENT_NAME,
  clearStoredToken,
  getStoredToken,
} from "@securecy/config/auth-storage";
import type { ApiResponse, MediaFile, MediaVisibility } from "@securecy/types";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";
const rawTenantId = process.env.NEXT_PUBLIC_TENANT_ID;
const parsedTenantId = rawTenantId ? Number(rawTenantId) : Number.NaN;
const tenantSlug = process.env.NEXT_PUBLIC_TENANT_SLUG ?? "securecy";

export const tenantAuthPayload = Number.isFinite(parsedTenantId)
  ? { tenant_id: parsedTenantId }
  : { tenant_slug: tenantSlug };

function handleUnauthorized() {
  clearStoredToken();

  if (typeof window === "undefined") {
    return;
  }

  window.dispatchEvent(new Event(UNAUTHORIZED_EVENT_NAME));

  if (window.location.pathname === "/login") {
    return;
  }

  const next = `${window.location.pathname}${window.location.search}`;
  window.location.assign(`/login?next=${encodeURIComponent(next)}`);
}

function handleForbidden(error: ApiClientError) {
  if (typeof window === "undefined") {
    return;
  }

  window.dispatchEvent(
    new CustomEvent(FORBIDDEN_EVENT_NAME, {
      detail: { message: error.message },
    }),
  );
}

export const api = createApiClient({
  baseUrl: API_BASE_URL,
  getAccessToken: getStoredToken,
  onUnauthorized: handleUnauthorized,
  onForbidden: handleForbidden,
});

export interface UploadMediaFileOptions {
  visibility?: MediaVisibility;
  signal?: AbortSignal;
  onProgress?: (progress: number) => void;
}

export function uploadMediaFile(
  file: File,
  options: UploadMediaFileOptions = {},
): Promise<MediaFile> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    const token = getStoredToken();
    const url = buildApiUrl(API_BASE_URL, "/media/upload");

    formData.append("file", file);
    formData.append("visibility", options.visibility ?? "private");

    xhr.open("POST", url.toString());
    xhr.responseType = "json";
    xhr.setRequestHeader("Accept", "application/json");

    if (token) {
      xhr.setRequestHeader("Authorization", `Bearer ${token}`);
    }

    xhr.upload.addEventListener("progress", (event) => {
      if (!event.lengthComputable) {
        return;
      }

      options.onProgress?.(Math.round((event.loaded / event.total) * 100));
    });

    xhr.addEventListener("load", () => {
      const json = parseXhrResponse<ApiResponse<MediaFile>>(xhr);

      if (xhr.status >= 200 && xhr.status < 300 && json?.data) {
        options.onProgress?.(100);
        resolve(json.data);
        return;
      }

      const error = new ApiClientError(xhr.status, json?.errors ?? [], json);

      if (xhr.status === 401) {
        handleUnauthorized();
      }

      if (xhr.status === 403) {
        handleForbidden(error);
      }

      reject(error);
    });

    xhr.addEventListener("error", () => {
      reject(new Error("Upload failed due to a network error."));
    });

    xhr.addEventListener("abort", () => {
      reject(new DOMException("Upload aborted.", "AbortError"));
    });

    if (options.signal) {
      const abortHandler = () => {
        xhr.abort();
      };

      if (options.signal.aborted) {
        xhr.abort();
        return;
      }

      options.signal.addEventListener("abort", abortHandler, { once: true });

      xhr.addEventListener("loadend", () => {
        options.signal?.removeEventListener("abort", abortHandler);
      }, { once: true });
    }

    xhr.send(formData);
  });
}

function parseXhrResponse<T>(xhr: XMLHttpRequest): T | null {
  if (xhr.response && typeof xhr.response === "object") {
    return xhr.response as T;
  }

  if (!xhr.responseText) {
    return null;
  }

  try {
    return JSON.parse(xhr.responseText) as T;
  } catch {
    return null;
  }
}

function buildApiUrl(baseUrl: string, path: string): URL {
  const normalizedBaseUrl = baseUrl.replace(/\/+$/, "");
  const normalizedPath = path.replace(/^\/+/, "");

  return new URL(`${normalizedBaseUrl}/${normalizedPath}`);
}
