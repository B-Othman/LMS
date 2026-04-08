export const AUTH_COOKIE_NAME = "securecy_auth_token";
export const AUTH_STORAGE_KEY = "securecy_auth_token";
export const UNAUTHORIZED_EVENT_NAME = "securecy:unauthorized";
export const FORBIDDEN_EVENT_NAME = "securecy:forbidden";
const TOKEN_MAX_AGE_SECONDS = 60 * 60 * 24 * 7;

export function getStoredToken(): string | null {
  if (typeof document === "undefined") {
    return null;
  }

  const cookieValue = readCookie(AUTH_COOKIE_NAME);
  if (cookieValue) {
    return cookieValue;
  }

  try {
    return window.localStorage.getItem(AUTH_STORAGE_KEY);
  } catch {
    return null;
  }
}

export function persistToken(token: string): void {
  if (typeof document === "undefined") {
    return;
  }

  const secure = window.location.protocol === "https:" ? "; Secure" : "";
  document.cookie = `${AUTH_COOKIE_NAME}=${encodeURIComponent(token)}; Max-Age=${TOKEN_MAX_AGE_SECONDS}; Path=/; SameSite=Lax${secure}`;

  try {
    window.localStorage.setItem(AUTH_STORAGE_KEY, token);
  } catch {
    // Ignore storage quota or privacy mode failures.
  }
}

export function clearStoredToken(): void {
  if (typeof document === "undefined") {
    return;
  }

  const secure = window.location.protocol === "https:" ? "; Secure" : "";
  document.cookie = `${AUTH_COOKIE_NAME}=; Max-Age=0; Path=/; SameSite=Lax${secure}`;

  try {
    window.localStorage.removeItem(AUTH_STORAGE_KEY);
  } catch {
    // Ignore storage failures while clearing auth state.
  }
}

function readCookie(name: string): string | null {
  const parts = document.cookie
    .split(";")
    .map((part) => part.trim())
    .filter(Boolean);

  for (const part of parts) {
    const [cookieName, ...valueParts] = part.split("=");
    if (cookieName === name) {
      return decodeURIComponent(valueParts.join("="));
    }
  }

  return null;
}
