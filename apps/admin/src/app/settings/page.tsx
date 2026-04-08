import { EmptyState, ProtectedRoute } from "@securecy/ui";

export default function SettingsPage() {
  return (
    <ProtectedRoute requiredPermissions={["settings.manage"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <h1 className="text-h1 text-night-800">Settings</h1>
        <p className="mt-2 text-body-lg text-neutral-500">
          Manage organization preferences, branding, and operational defaults.
        </p>

        <div className="mt-8">
          <EmptyState
            title="Settings are not configured yet"
            description="Tenant-level settings and branding controls will appear here as they are added."
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
