export interface Certificate {
  id: number;
  tenantId: number;
  userId: number;
  courseId: number;
  enrollmentId: number;
  certificateNumber: string;
  issuedAt: string;
  expiresAt: string | null;
  pdfUrl: string | null;
  createdAt: string;
  updatedAt: string;
}
