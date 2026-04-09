import { CertificateTemplateFormPage } from "@/app/certificates/_components/certificate-template-form-page";

interface EditCertificateTemplatePageProps {
  params: Promise<{ id: string }>;
}

export default async function EditCertificateTemplatePage({
  params,
}: EditCertificateTemplatePageProps) {
  const { id } = await params;

  return <CertificateTemplateFormPage templateId={Number(id)} />;
}
