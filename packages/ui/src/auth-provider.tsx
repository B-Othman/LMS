"use client";

import type { ApiClient, ApiClientError } from "@securecy/config/api-client";
import {
  FORBIDDEN_EVENT_NAME,
  UNAUTHORIZED_EVENT_NAME,
  clearStoredToken,
  getStoredToken,
  persistToken,
} from "@securecy/config/auth-storage";
import type { AuthResponse, User } from "@securecy/types";
import { useRouter } from "next/navigation";
import {
  createContext,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";

interface AuthProviderProps {
  api: ApiClient;
  tenantAuthPayload: {
    tenant_id?: number;
    tenant_slug?: string;
  };
  children: ReactNode;
  loginPath?: string;
}

interface AuthContextValue {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  forbiddenMessage: string | null;
  clearForbiddenMessage: () => void;
  login: (email: string, password: string) => Promise<User>;
  logout: () => Promise<void>;
  hasRole: (role: string) => boolean;
  hasPermission: (permission: string) => boolean;
  hasAnyPermission: (permissions: string[]) => boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({
  api,
  tenantAuthPayload,
  children,
  loginPath = "/login",
}: AuthProviderProps) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [forbiddenMessage, setForbiddenMessage] = useState<string | null>(null);

  useEffect(() => {
    function handleForbidden(event: Event) {
      const detail = (event as CustomEvent<{ message?: string }>).detail;
      setForbiddenMessage(detail?.message ?? "You do not have permission to access this area.");
    }

    function handleUnauthorized() {
      setUser(null);
      setForbiddenMessage(null);
      setIsLoading(false);
    }

    window.addEventListener(FORBIDDEN_EVENT_NAME, handleForbidden as EventListener);
    window.addEventListener(UNAUTHORIZED_EVENT_NAME, handleUnauthorized);

    const token = getStoredToken();
    if (!token) {
      setIsLoading(false);

      return () => {
        window.removeEventListener(FORBIDDEN_EVENT_NAME, handleForbidden as EventListener);
        window.removeEventListener(UNAUTHORIZED_EVENT_NAME, handleUnauthorized);
      };
    }

    let cancelled = false;

    api
      .get<User>("/me")
      .then((response) => {
        if (!cancelled) {
          setUser(response.data ?? null);
        }
      })
      .catch(() => {
        if (!cancelled) {
          clearStoredToken();
          setUser(null);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLoading(false);
        }
      });

    return () => {
      cancelled = true;
      window.removeEventListener(FORBIDDEN_EVENT_NAME, handleForbidden as EventListener);
      window.removeEventListener(UNAUTHORIZED_EVENT_NAME, handleUnauthorized);
    };
  }, [api]);

  async function login(email: string, password: string): Promise<User> {
    setIsLoading(true);
    setForbiddenMessage(null);

    try {
      const response = await api.post<AuthResponse>(
        "/auth/login",
        { email, password, ...tenantAuthPayload },
        { handleAuthErrors: false },
      );

      const auth = response.data;
      if (!auth) {
        throw new Error("Login response did not include user data.");
      }

      persistToken(auth.token);
      setUser(auth.user);

      return auth.user;
    } finally {
      setIsLoading(false);
    }
  }

  async function logout(): Promise<void> {
    try {
      await api.post("/auth/logout", undefined, { handleAuthErrors: false });
    } catch {
      // Clear the client session even if the backend token is already invalid.
    }

    clearStoredToken();
    setUser(null);
    setForbiddenMessage(null);
    router.replace(loginPath);
  }

  function hasRole(role: string): boolean {
    return user?.roles.some((value) => value === role) ?? false;
  }

  function hasPermission(permission: string): boolean {
    return user?.permissions.includes(permission) ?? false;
  }

  function hasAnyPermission(permissions: string[]): boolean {
    return permissions.some((permission) => hasPermission(permission));
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: user !== null,
        forbiddenMessage,
        clearForbiddenMessage: () => setForbiddenMessage(null),
        login,
        logout,
        hasRole,
        hasPermission,
        hasAnyPermission,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider.");
  }

  return context;
}

export function isApiClientError(error: unknown): error is ApiClientError {
  return typeof error === "object" && error !== null && "status" in error && "errors" in error;
}
