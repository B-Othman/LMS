import { PublicCertificateVerificationPage } from "@/components/public-certificate-verification-page";

interface VerifyCertificatePageProps {
  params: Promise<{ code: string }>;
}

export default async function VerifyCertificatePage({
  params,
}: VerifyCertificatePageProps) {
  const { code } = await params;

  return <PublicCertificateVerificationPage verificationCode={code} />;
}
