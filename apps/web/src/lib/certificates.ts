import type {
  Certificate,
  CertificateDownloadLink,
  PublicCertificateVerification,
} from "@securecy/types";

import { api } from "./api";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export async function fetchMyCertificates(): Promise<Certificate[]> {
  const response = await api.get<Certificate[]>("/my/certificates");

  return response.data ?? [];
}

export async function fetchMyCertificateDownload(id: number): Promise<CertificateDownloadLink> {
  const response = await api.get<CertificateDownloadLink>(`/my/certificates/${id}/download`);

  if (!response.data) {
    throw new Error("The certificate download link is unavailable.");
  }

  return response.data;
}

export async function fetchPublicCertificateVerification(
  verificationCode: string,
): Promise<PublicCertificateVerification> {
  const response = await fetch(buildApiUrl(`/certificates/verify/${verificationCode}`), {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });

  const json = (await response.json().catch(() => null)) as { data?: PublicCertificateVerification } | null;

  if (!response.ok || !json?.data) {
    throw new Error("The certificate verification record could not be loaded.");
  }

  return json.data;
}

function buildApiUrl(path: string): string {
  const normalizedBaseUrl = API_BASE_URL.replace(/\/+$/, "");
  const normalizedPath = path.replace(/^\/+/, "");

  return `${normalizedBaseUrl}/${normalizedPath}`;
}
