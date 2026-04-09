import { ApiClientError } from "@securecy/config/api-client";
import {
  FORBIDDEN_EVENT_NAME,
  UNAUTHORIZED_EVENT_NAME,
  clearStoredToken,
  getStoredToken,
} from "@securecy/config/auth-storage";
import type {
  ApiResponse,
  Certificate,
  CertificateDownloadLink,
  CertificateListFilters,
  CertificateTemplate,
  CreateCertificateTemplatePayload,
  PaginatedResponse,
  RevokeCertificatePayload,
  UpdateCertificateTemplatePayload,
} from "@securecy/types";

import { api } from "./api";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export async function fetchCertificateTemplates(): Promise<CertificateTemplate[]> {
  const response = await api.get<CertificateTemplate[]>("/certificate-templates");

  return response.data ?? [];
}

export async function fetchCertificateTemplate(id: number): Promise<CertificateTemplate> {
  const response = await api.get<CertificateTemplate>(`/certificate-templates/${id}`);

  if (!response.data) {
    throw new Error("Certificate template not found.");
  }

  return response.data;
}

export async function createCertificateTemplate(
  payload: CreateCertificateTemplatePayload,
  backgroundImage?: File | null,
): Promise<CertificateTemplate> {
  const response = await sendCertificateTemplateForm("/certificate-templates", payload, backgroundImage);

  if (!response.data) {
    throw new Error("The certificate template could not be created.");
  }

  return response.data;
}

export async function updateCertificateTemplate(
  id: number,
  payload: UpdateCertificateTemplatePayload,
  backgroundImage?: File | null,
): Promise<CertificateTemplate> {
  const response = await sendCertificateTemplateForm(
    `/certificate-templates/${id}`,
    payload,
    backgroundImage,
    "PUT",
  );

  if (!response.data) {
    throw new Error("The certificate template could not be updated.");
  }

  return response.data;
}

export async function deleteCertificateTemplate(id: number): Promise<void> {
  await api.delete(`/certificate-templates/${id}`);
}

export async function fetchIssuedCertificates(
  filters: CertificateListFilters,
): Promise<PaginatedResponse<Certificate>> {
  return api.paginated<Certificate>("/certificates", {
    params: filters as Record<string, string | number | boolean | null | undefined>,
  });
}

export async function downloadTemplatePreview(id: number): Promise<Blob> {
  const url = buildApiUrl(`/certificate-templates/${id}/preview`);
  const token = getStoredToken();

  const response = await fetch(url, {
    method: "GET",
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  });

  if (!response.ok) {
    await handleMultipartError(response);
  }

  return response.blob();
}

export async function downloadCertificate(id: number): Promise<CertificateDownloadLink> {
  const response = await api.get<CertificateDownloadLink>(`/certificates/${id}/download`);

  if (!response.data) {
    throw new Error("The certificate download link is unavailable.");
  }

  return response.data;
}

export async function revokeCertificate(
  id: number,
  payload: RevokeCertificatePayload,
): Promise<Certificate> {
  const response = await api.post<Certificate>(`/certificates/${id}/revoke`, payload);

  if (!response.data) {
    throw new Error("The certificate could not be revoked.");
  }

  return response.data;
}

async function sendCertificateTemplateForm(
  path: string,
  payload: CreateCertificateTemplatePayload | UpdateCertificateTemplatePayload,
  backgroundImage?: File | null,
  method: "POST" | "PUT" = "POST",
): Promise<ApiResponse<CertificateTemplate>> {
  const url = buildApiUrl(path);
  const token = getStoredToken();
  const formData = new FormData();

  for (const [key, value] of Object.entries(payload)) {
    if (value === undefined) {
      continue;
    }

    if (typeof value === 'boolean') {
      formData.append(key, value ? "1" : "0");
      continue;
    }

    if (value === null) {
      formData.append(key, "");
      continue;
    }

    formData.append(key, String(value));
  }

  if (backgroundImage) {
    formData.append("background_image", backgroundImage);
  }

  if (method === "PUT") {
    formData.append("_method", "PUT");
  }

  const response = await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: formData,
  });

  if (!response.ok) {
    await handleMultipartError(response);
  }

  return response.json() as Promise<ApiResponse<CertificateTemplate>>;
}

async function handleMultipartError(response: Response): Promise<never> {
  const json = (await response.json().catch(() => null)) as ApiResponse<unknown> | null;
  const error = new ApiClientError(response.status, json?.errors ?? [], json);

  if (response.status === 401) {
    clearStoredToken();
    window.dispatchEvent(new Event(UNAUTHORIZED_EVENT_NAME));
  }

  if (response.status === 403) {
    window.dispatchEvent(
      new CustomEvent(FORBIDDEN_EVENT_NAME, {
        detail: { message: error.message },
      }),
    );
  }

  throw error;
}

function buildApiUrl(path: string): string {
  const normalizedBaseUrl = API_BASE_URL.replace(/\/+$/, "");
  const normalizedPath = path.replace(/^\/+/, "");

  return `${normalizedBaseUrl}/${normalizedPath}`;
}
