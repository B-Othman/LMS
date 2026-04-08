import type { ApiResponse, ApiError, PaginatedResponse } from "@securecy/types";

export class ApiClientError extends Error {
  constructor(
    public status: number,
    public errors: ApiError[],
  ) {
    super(errors[0]?.message ?? `API error ${status}`);
    this.name = "ApiClientError";
  }
}

interface RequestOptions extends Omit<RequestInit, "body"> {
  params?: Record<string, string | number | boolean | undefined>;
  body?: unknown;
}

export function createApiClient(baseUrl: string) {
  let token: string | null = null;

  function setToken(t: string | null) {
    token = t;
  }

  async function request<T>(
    method: string,
    path: string,
    options: RequestOptions = {},
  ): Promise<T> {
    const { params, body, headers: customHeaders, ...rest } = options;

    const url = new URL(path, baseUrl);
    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined) {
          url.searchParams.set(key, String(value));
        }
      }
    }

    const headers: Record<string, string> = {
      Accept: "application/json",
      ...((customHeaders as Record<string, string>) ?? {}),
    };

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
      const json = await res.json().catch(() => ({ errors: [] }));
      throw new ApiClientError(res.status, json.errors ?? []);
    }

    if (res.status === 204) {
      return undefined as T;
    }

    return res.json() as Promise<T>;
  }

  return {
    setToken,

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
