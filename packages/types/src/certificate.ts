export type CertificateTemplateLayout = "landscape" | "portrait";
export type CertificateTemplateStatus = "active" | "inactive";
export type CertificateStatus = "active" | "expired" | "revoked";
export type PublicCertificateVerificationStatus = "valid" | "expired" | "revoked";

export interface CertificateTemplateSummary {
  id: number;
  name: string;
  layout: CertificateTemplateLayout;
  status: CertificateTemplateStatus;
  is_default?: boolean;
}

export interface CertificateTemplate {
  id: number;
  tenant_id: number;
  name: string;
  description: string | null;
  layout: CertificateTemplateLayout;
  background_image_path: string | null;
  background_image_url: string | null;
  content_html: string;
  is_default: boolean;
  status: CertificateTemplateStatus;
  issued_count: number;
  created_by: number;
  creator: {
    id: number;
    full_name: string;
    email: string;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface Certificate {
  id: number;
  enrollment_id: number;
  user_id: number;
  course_id: number;
  tenant_id: number;
  template_id: number;
  issued_at: string;
  expires_at: string | null;
  file_ready: boolean;
  verification_code: string;
  status: CertificateStatus;
  revoked_at: string | null;
  revoked_reason: string | null;
  metadata: Record<string, string | null>;
  user: {
    id: number;
    full_name: string;
    email: string;
  } | null;
  course: {
    id: number;
    title: string;
    slug: string;
  } | null;
  template: CertificateTemplateSummary | null;
  created_at: string;
  updated_at: string;
}

export interface CertificateDownloadLink {
  url: string;
  expires_at: string;
}

export interface CertificateListFilters {
  course_id?: number | "";
  user_id?: number | "";
  search?: string;
  status?: CertificateStatus | "";
  issued_from?: string;
  issued_to?: string;
  sort_by?: "issued_at" | "expires_at" | "created_at";
  sort_dir?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export interface CreateCertificateTemplatePayload {
  name: string;
  description?: string;
  layout?: CertificateTemplateLayout;
  content_html: string;
  is_default?: boolean;
  status?: CertificateTemplateStatus;
}

export interface UpdateCertificateTemplatePayload {
  name?: string;
  description?: string | null;
  layout?: CertificateTemplateLayout;
  content_html?: string;
  is_default?: boolean;
  status?: CertificateTemplateStatus;
  clear_background_image?: boolean;
}

export interface RevokeCertificatePayload {
  reason: string;
}

export interface PublicCertificateVerification {
  verification_code: string;
  status: PublicCertificateVerificationStatus;
  learner_name: string;
  course_title: string;
  issued_at: string | null;
  expires_at: string | null;
  revoked_at: string | null;
  revoked_reason: string | null;
}
