"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { fetchPublicCertificateVerification } from "@/lib/certificates";
import type { PublicCertificateVerification } from "@securecy/types";

import { VerificationBadge } from "./verification-badge";

interface PublicCertificateVerificationPageProps {
  verificationCode: string;
}

export function PublicCertificateVerificationPage({
  verificationCode,
}: PublicCertificateVerificationPageProps) {
  const [record, setRecord] = useState<PublicCertificateVerification | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    fetchPublicCertificateVerification(verificationCode)
      .then((result) => {
        if (!cancelled) {
          setRecord(result);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError("This verification code could not be found.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [verificationCode]);

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(91,146,198,0.2),_rgba(255,255,255,1)_62%)] px-6 py-10">
      <div className="mx-auto max-w-5xl">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p className="text-overline uppercase tracking-[0.28em] text-primary-700">Securecy</p>
            <p className="mt-2 text-body-md text-neutral-500">Certificate verification</p>
          </div>
          <Link href="/login" className="text-body-sm font-semibold text-primary-700 underline">
            Securecy LMS
          </Link>
        </div>

        <div className="mt-10 rounded-[36px] border border-neutral-200 bg-white/95 p-8 shadow-card">
          {isLoading ? (
            <div className="space-y-6">
              <div className="h-28 animate-pulse rounded-[32px] bg-neutral-100" />
              <div className="h-48 animate-pulse rounded-[32px] bg-neutral-100" />
            </div>
          ) : error || !record ? (
            <div className="rounded-[32px] border border-error-200 bg-error-50 px-6 py-6 text-body-lg text-error-700">
              {error ?? "This verification code could not be found."}
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-8 lg:grid-cols-[360px_minmax(0,1fr)]">
              <VerificationBadge status={record.status} />

              <div className="space-y-6">
                <div>
                  <p className="text-overline uppercase tracking-[0.18em] text-neutral-500">Certificate record</p>
                  <h2 className="mt-3 text-h1 text-night-900">{record.course_title}</h2>
                  <p className="mt-3 text-body-lg text-neutral-600">
                    Issued to {record.learner_name}
                  </p>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <DetailCard label="Issued on" value={formatDate(record.issued_at)} />
                  <DetailCard label="Expiry" value={formatDate(record.expires_at)} />
                  <DetailCard label="Verification code" value={record.verification_code} />
                  <DetailCard label="Revoked on" value={formatDate(record.revoked_at)} />
                </div>

                {record.revoked_reason ? (
                  <div className="rounded-[28px] border border-error-200 bg-error-50 px-5 py-4">
                    <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-error-700">Revocation reason</p>
                    <p className="mt-2 text-body-md text-error-700">{record.revoked_reason}</p>
                  </div>
                ) : null}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function DetailCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-[28px] border border-neutral-200 bg-neutral-50 px-5 py-4">
      <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">{label}</p>
      <p className="mt-3 text-body-lg font-semibold text-night-900">{value}</p>
    </div>
  );
}

function formatDate(value: string | null): string {
  if (!value) {
    return "Not set";
  }

  return new Intl.DateTimeFormat("en", {
    month: "long",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
}
