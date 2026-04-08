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
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState, useTransition } from "react";

export default function LoginPage() {
  return (
    <Suspense fallback={<LoginPageFallback />}>
      <LoginPageContent />
    </Suspense>
  );
}

function LoginPageContent() {
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
      title="Welcome back"
      description="Sign in to continue your learning journey with Securecy."
      footer={
        <>
          Need help getting back in?{" "}
          <Link href="/forgot-password" className="font-semibold text-primary-700 underline">
            Reset your password
          </Link>
        </>
      }
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
            placeholder="you@securecy.com"
          />
          {fieldErrors.email ? <p className="text-body-sm text-error-500">{fieldErrors.email}</p> : null}
        </div>

        <div className="space-y-2">
          <div className="flex items-center justify-between gap-3">
            <Label htmlFor="password">Password</Label>
            <Link href="/forgot-password" className="text-body-sm font-semibold text-primary-700 underline">
              Forgot password?
            </Link>
          </div>
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

function LoginPageFallback() {
  return (
    <AuthPageShell
      title="Welcome back"
      description="Sign in to continue your learning journey with Securecy."
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
