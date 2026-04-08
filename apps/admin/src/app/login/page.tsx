"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import {
  Alert,
  AuthPageShell,
  Button,
  Input,
  Label,
  isApiClientError,
  useAuth,
} from "@securecy/ui";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState, useTransition } from "react";

export default function AdminLoginPage() {
  return (
    <Suspense fallback={<AdminLoginFallback />}>
      <AdminLoginContent />
    </Suspense>
  );
}

function AdminLoginContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login, isAuthenticated, isLoading } = useAuth();
  const [isPending, startTransition] = useTransition();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (isAuthenticated) {
      router.replace(getNextPath(searchParams.get("next")));
    }
  }, [isAuthenticated, router, searchParams]);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setGeneralError(null);
    setFieldErrors({});

    try {
      await login(email, password);
      startTransition(() => {
        router.replace(getNextPath(searchParams.get("next")));
      });
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "Unable to sign in.");
        return;
      }

      setGeneralError("Unable to sign in. Please try again.");
    }
  }

  return (
    <AuthPageShell
      eyebrow="Securecy Admin"
      title="Admin Portal"
      description="Sign in to manage users, courses, enrollments, and reporting."
    >
      <form className="space-y-5" onSubmit={handleSubmit}>
        {generalError ? <Alert tone="error">{generalError}</Alert> : null}

        <div className="space-y-2">
          <Label htmlFor="email">Email Address</Label>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            error={Boolean(fieldErrors.email)}
            placeholder="admin@securecy.com"
          />
          {fieldErrors.email ? <p className="text-body-sm text-error-500">{fieldErrors.email}</p> : null}
        </div>

        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <Input
            id="password"
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            error={Boolean(fieldErrors.password)}
            placeholder="Enter your password"
          />
          {fieldErrors.password ? (
            <p className="text-body-sm text-error-500">{fieldErrors.password}</p>
          ) : null}
        </div>

        <Button type="submit" className="w-full" disabled={isPending || isLoading}>
          {isPending || isLoading ? "Signing in..." : "Sign In"}
        </Button>
      </form>
    </AuthPageShell>
  );
}

function AdminLoginFallback() {
  return (
    <AuthPageShell
      eyebrow="Securecy Admin"
      title="Admin Portal"
      description="Sign in to manage users, courses, enrollments, and reporting."
    >
      <div className="space-y-3">
        <div className="h-12 rounded-lg bg-neutral-100" />
        <div className="h-12 rounded-lg bg-neutral-100" />
        <div className="h-11 rounded-lg bg-primary-100" />
      </div>
    </AuthPageShell>
  );
}

function getNextPath(next: string | null): string {
  if (next && next.startsWith("/")) {
    return next;
  }

  return "/dashboard";
}
