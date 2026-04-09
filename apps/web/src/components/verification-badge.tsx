import type { PublicCertificateVerificationStatus } from "@securecy/types";

interface VerificationBadgeProps {
  status: PublicCertificateVerificationStatus;
}

export function VerificationBadge({ status }: VerificationBadgeProps) {
  const config = statusConfig(status);

  return (
    <div className={`rounded-[32px] border px-6 py-6 ${config.containerClassName}`}>
      <div className={`inline-flex h-16 w-16 items-center justify-center rounded-full ${config.iconClassName}`}>
        <StatusGlyph status={status} />
      </div>
      <p className="mt-5 text-overline uppercase tracking-[0.18em] text-neutral-500">Verification status</p>
      <h1 className="mt-2 text-h1 text-night-900">{config.label}</h1>
      <p className="mt-3 text-body-md text-neutral-600">{config.description}</p>
    </div>
  );
}

function statusConfig(status: PublicCertificateVerificationStatus) {
  if (status === "valid") {
    return {
      label: "Valid",
      description: "This certificate is active and has not been revoked.",
      containerClassName: "border-success-200 bg-success-50",
      iconClassName: "bg-success-500 text-white",
    };
  }

  if (status === "expired") {
    return {
      label: "Expired",
      description: "This certificate was issued successfully, but it is no longer valid because it expired.",
      containerClassName: "border-warning-200 bg-warning-50",
      iconClassName: "bg-warning-500 text-white",
    };
  }

  return {
    label: "Revoked",
    description: "This certificate record exists, but it has been revoked and should not be accepted as valid.",
    containerClassName: "border-error-200 bg-error-50",
    iconClassName: "bg-error-500 text-white",
  };
}

function StatusGlyph({ status }: VerificationBadgeProps) {
  if (status === "valid") {
    return (
      <svg viewBox="0 0 24 24" className="h-8 w-8" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="m5 13 4 4L19 7" />
      </svg>
    );
  }

  if (status === "expired") {
    return (
      <svg viewBox="0 0 24 24" className="h-8 w-8" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M12 7v6" />
        <path d="M12 17h.01" />
        <path d="M10.3 3.9 2.9 17a2 2 0 0 0 1.75 3h14.7A2 2 0 0 0 21.1 17L13.7 3.9a2 2 0 0 0-3.4 0Z" />
      </svg>
    );
  }

  return (
    <svg viewBox="0 0 24 24" className="h-8 w-8" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="m9 9 6 6" />
      <path d="m15 9-6 6" />
    </svg>
  );
}
