"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import { Alert, AuthPageShell, Button, Input, Label, isApiClientError } from "@securecy/ui";
import Link from "next/link";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState, useTransition } from "react";

import { api } from "@/lib/api";

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={<ResetPasswordFallback />}>
      <ResetPasswordPageContent />
    </Suspense>
  );
}

function ResetPasswordPageContent() {
  const params = useParams<{ token: string }>();
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isPending, startTransition] = useTransition();
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const email = searchParams.get("email") ?? "";

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setGeneralError(null);
    setSuccessMessage(null);
    setFieldErrors({});

    if (!email) {
      setGeneralError("This reset link is missing the account email. Request a new reset link.");
      return;
    }

    try {
      const response = await api.post(
        "/auth/reset-password",
        {
          token: params.token,
          email,
          password,
          password_confirmation: passwordConfirmation,
        },
        { handleAuthErrors: false },
      );

      setSuccessMessage(response.message ?? "Password has been reset successfully.");
      startTransition(() => {
        window.setTimeout(() => router.replace("/login"), 800);
      });
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "Unable to reset password.");
      } else {
        setGeneralError("Unable to reset password. Please request a new reset link.");
      }
    }
  }

  return (
    <AuthPageShell
      title="Create a new password"
      description="Choose a strong password for your Securecy LMS account."
      footer={
        <>
          Need a new reset email?{" "}
          <Link href="/forgot-password" className="font-semibold text-primary-700 underline">
            Request another link
          </Link>
        </>
      }
    >
      <form className="space-y-5" onSubmit={handleSubmit}>
        {!email ? (
          <Alert tone="error">
            This reset link is incomplete. Request a fresh reset email to continue.
          </Alert>
        ) : null}
        {successMessage ? <Alert tone="success">{successMessage}</Alert> : null}
        {generalError ? <Alert tone="error">{generalError}</Alert> : null}

        <div className="space-y-2">
          <Label htmlFor="password">New Password</Label>
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            error={Boolean(fieldErrors.password)}
            placeholder="Enter your new password"
          />
          {fieldErrors.password ? (
            <p className="text-body-sm text-error-500">{fieldErrors.password}</p>
          ) : null}
        </div>

        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirm Password</Label>
          <Input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
            error={Boolean(fieldErrors.password_confirmation)}
            placeholder="Confirm your new password"
          />
          {fieldErrors.password_confirmation ? (
            <p className="text-body-sm text-error-500">{fieldErrors.password_confirmation}</p>
          ) : null}
        </div>

        <Button type="submit" className="w-full" disabled={!email || isPending}>
          {isPending ? "Updating password..." : "Reset Password"}
        </Button>
      </form>
    </AuthPageShell>
  );
}

function ResetPasswordFallback() {
  return (
    <AuthPageShell
      title="Create a new password"
      description="Choose a strong password for your Securecy LMS account."
    >
      <div className="space-y-3">
        <div className="h-12 rounded-lg bg-neutral-100" />
        <div className="h-12 rounded-lg bg-neutral-100" />
        <div className="h-11 rounded-lg bg-primary-100" />
      </div>
    </AuthPageShell>
  );
}
