"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import { Alert, AuthPageShell, Button, Input, Label, isApiClientError } from "@securecy/ui";
import Link from "next/link";
import { useState } from "react";

import { api, tenantAuthPayload } from "@/lib/api";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmitting(true);
    setSuccessMessage(null);
    setGeneralError(null);
    setFieldErrors({});

    try {
      const response = await api.post(
        "/auth/forgot-password",
        { email, ...tenantAuthPayload },
        { handleAuthErrors: false },
      );

      setSuccessMessage(
        response.message ?? "If an account exists for that email, a reset link has been sent.",
      );
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "Unable to send reset email.");
      } else {
        setGeneralError("Unable to send reset email. Please try again.");
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <AuthPageShell
      title="Reset your password"
      description="Enter your account email and we’ll send you a secure password reset link."
      footer={
        <>
          Remembered your password?{" "}
          <Link href="/login" className="font-semibold text-primary-700 underline">
            Back to login
          </Link>
        </>
      }
    >
      <form className="space-y-5" onSubmit={handleSubmit}>
        {successMessage ? <Alert tone="success">{successMessage}</Alert> : null}
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

        <Button type="submit" className="w-full" disabled={isSubmitting}>
          {isSubmitting ? "Sending link..." : "Send Reset Link"}
        </Button>
      </form>
    </AuthPageShell>
  );
}
