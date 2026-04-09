"use client";

import type { Certificate } from "@securecy/types";
import { useEffect, useState } from "react";

import {
  EmptyState,
  ProtectedRoute,
  useToast,
} from "@securecy/ui";

import {
  fetchMyCertificateDownload,
  fetchMyCertificates,
} from "@/lib/certificates";

import { CertificateCard } from "./certificate-card";

export function LearnerCertificatesPage() {
  const { showToast } = useToast();
  const [certificates, setCertificates] = useState<Certificate[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    fetchMyCertificates()
      .then((rows) => {
        if (!cancelled) {
          setCertificates(rows);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setCertificates([]);
          showToast({
            tone: "error",
            title: "Certificates unavailable",
            message: "Your certificates could not be loaded right now.",
          });
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
  }, [showToast]);

  async function handleDownload(certificate: Certificate) {
    try {
      const download = await fetchMyCertificateDownload(certificate.id);
      window.open(download.url, "_blank", "noopener,noreferrer");
    } catch {
      showToast({
        tone: "error",
        title: "Download unavailable",
        message: "The certificate PDF is still being generated.",
      });
    }
  }

  async function handleShare(certificate: Certificate) {
    const verificationUrl = `${window.location.origin}/verify/${certificate.verification_code}`;

    try {
      await navigator.clipboard.writeText(verificationUrl);
      showToast({
        tone: "success",
        title: "Verification link copied",
        message: "The public verification link is ready to share.",
      });
    } catch {
      showToast({
        tone: "error",
        title: "Copy failed",
        message: "The verification link could not be copied to the clipboard.",
      });
    }
  }

  return (
    <ProtectedRoute requiredPermissions={["certificates.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div>
          <h1 className="text-h1 text-night-800">Certificates</h1>
          <p className="mt-2 text-body-lg text-neutral-500">
            Download earned certificates and share public verification links.
          </p>
        </div>

        <div className="mt-8">
          {certificates.length === 0 ? (
            <EmptyState
              title={isLoading ? "Loading certificates..." : "No certificates yet"}
              description="Complete a certificate-enabled course to see your achievements here."
            />
          ) : (
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
              {certificates.map((certificate) => (
                <CertificateCard
                  key={certificate.id}
                  certificate={certificate}
                  onDownload={handleDownload}
                  onShare={handleShare}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </ProtectedRoute>
  );
}
