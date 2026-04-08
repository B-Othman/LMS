import type { ApiResponse, ApiError, PaginatedResponse } from "@securecy/types";

export class ApiClientError extends Error {
  constructor(
    public status: number,
    public errors: ApiError[],
    public response: ApiResponse<unknown> | null = null,
  ) {
    super(errors[0]?.message ?? `API error ${status}`);
    this.name = "ApiClientError";
  }
}

export interface RequestOptions extends Omit<RequestInit, "body"> {
  params?: Record<string, string | number | boolean | null | undefined>;
  body?: unknown;
  handleAuthErrors?: boolean;
}

interface CreateApiClientOptions {
  baseUrl: string;
  getAccessToken?: () => string | null;
  onUnauthorized?: () => void;
  onForbidden?: (error: ApiClientError) => void;
}

export function createApiClient({
  baseUrl,
  getAccessToken,
  onUnauthorized,
  onForbidden,
}: CreateApiClientOptions) {
  const normalizedBaseUrl = baseUrl.replace(/\/+$/, "");

  async function request<T>(
    method: string,
    path: string,
    options: RequestOptions = {},
  ): Promise<T> {
    const {
      params,
      body,
      handleAuthErrors = true,
      headers: customHeaders,
      ...rest
    } = options;

    const url = buildUrl(normalizedBaseUrl, path);
    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null) {
          url.searchParams.set(key, String(value));
        }
      }
    }

    const headers: Record<string, string> = {
      Accept: "application/json",
      ...((customHeaders as Record<string, string>) ?? {}),
    };

    const token = getAccessToken?.() ?? null;
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    if (body !== undefined) {
      headers["Content-Type"] = "application/json";
    }

    const res = await fetch(url.toString(), {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      ...rest,
    });

    if (!res.ok) {
      const json = (await res.json().catch(() => null)) as ApiResponse<unknown> | null;
      const error = new ApiClientError(res.status, json?.errors ?? [], json);

      if (handleAuthErrors) {
        if (res.status === 401) {
          onUnauthorized?.();
        }

        if (res.status === 403) {
          onForbidden?.(error);
        }
      }

      throw error;
    }

    if (res.status === 204) {
      return {} as T;
    }

    return res.json() as Promise<T>;
  }

  return {
    get<T>(path: string, options?: RequestOptions): Promise<ApiResponse<T>> {
      return request<ApiResponse<T>>("GET", path, options);
    },

    post<T>(path: string, body?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
      return request<ApiResponse<T>>("POST", path, { ...options, body });
    },

    put<T>(path: string, body?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
      return request<ApiResponse<T>>("PUT", path, { ...options, body });
    },

    patch<T>(path: string, body?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
      return request<ApiResponse<T>>("PATCH", path, { ...options, body });
    },

    delete<T = void>(path: string, options?: RequestOptions): Promise<ApiResponse<T>> {
      return request<ApiResponse<T>>("DELETE", path, options);
    },

    paginated<T>(path: string, options?: RequestOptions): Promise<PaginatedResponse<T>> {
      return request<PaginatedResponse<T>>("GET", path, options);
    },
  };
}

export type ApiClient = ReturnType<typeof createApiClient>;

export function getFieldErrors(errors: ApiError[] = []): Record<string, string> {
  return errors.reduce<Record<string, string>>((acc, error) => {
    if (error.field && !acc[error.field]) {
      acc[error.field] = error.message;
    }

    return acc;
  }, {});
}

function buildUrl(baseUrl: string, path: string): URL {
  if (/^https?:\/\//i.test(path)) {
    return new URL(path);
  }

  const normalizedPath = path.replace(/^\/+/, "");
  return new URL(`${baseUrl}/${normalizedPath}`);
}
