import { EmptyState, ProtectedRoute } from "@securecy/ui";

export default function CertificatesPage() {
  return (
    <ProtectedRoute requiredPermissions={["certificates.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <h1 className="text-h1 text-night-800">Certificates</h1>
        <p className="mt-2 text-body-lg text-neutral-500">
          View and download your earned certificates.
        </p>

        <div className="mt-8">
          <EmptyState
            title="No certificates yet"
            description="Complete a course to earn your first certificate."
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
