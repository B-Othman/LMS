"use client";

import type { Certificate } from "@securecy/types";

import { Badge, Button, Card, CopyIcon } from "@securecy/ui";

interface CertificateCardProps {
  certificate: Certificate;
  onDownload: (certificate: Certificate) => void;
  onShare: (certificate: Certificate) => void;
}

export function CertificateCard({
  certificate,
  onDownload,
  onShare,
}: CertificateCardProps) {
  return (
    <Card className="overflow-hidden p-0">
      <div className="border-b border-primary-100 bg-[radial-gradient(circle_at_top_left,_rgba(91,146,198,0.24),_rgba(255,255,255,1)_68%)] px-5 py-5">
        <div className="rounded-[24px] border border-primary-100 bg-white/90 px-5 py-6 shadow-card">
          <p className="text-overline uppercase tracking-[0.24em] text-primary-700">Securecy</p>
          <h3 className="mt-4 text-h3 text-night-900">Certificate</h3>
          <p className="mt-2 text-body-sm text-neutral-500">Issued for course completion</p>
          <p className="mt-5 text-body-md font-semibold text-night-900">
            {certificate.course?.title ?? "Course certificate"}
          </p>
        </div>
      </div>

      <div className="space-y-4 px-5 py-5">
        <div className="flex flex-wrap items-center gap-2">
          <Badge variant={statusVariant(certificate.status)}>{statusLabel(certificate.status)}</Badge>
          <span className="text-body-sm text-neutral-500">
            Issued {formatDate(certificate.issued_at)}
          </span>
        </div>

        <div className="space-y-2 text-body-sm text-neutral-600">
          <div className="flex items-center justify-between gap-3">
            <span>Verification code</span>
            <span className="font-semibold text-night-900">{certificate.verification_code}</span>
          </div>
          <div className="flex items-center justify-between gap-3">
            <span>Expiry</span>
            <span>{formatDate(certificate.expires_at)}</span>
          </div>
        </div>

        <div className="flex flex-wrap gap-3">
          <Button
            type="button"
            disabled={!certificate.file_ready}
            onClick={() => onDownload(certificate)}
          >
            {certificate.file_ready ? "Download" : "Preparing PDF"}
          </Button>
          <Button type="button" variant="secondary" onClick={() => onShare(certificate)}>
            <CopyIcon className="mr-2 h-4 w-4" />
            Share
          </Button>
        </div>
      </div>
    </Card>
  );
}

function statusVariant(status: Certificate["status"]): "success" | "warning" | "error" {
  if (status === "active") {
    return "success";
  }

  if (status === "expired") {
    return "warning";
  }

  return "error";
}

function statusLabel(status: Certificate["status"]): string {
  if (status === "active") {
    return "Active";
  }

  if (status === "expired") {
    return "Expired";
  }

  return "Revoked";
}

function formatDate(value: string | null): string {
  if (!value) {
    return "No expiry";
  }

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
}
