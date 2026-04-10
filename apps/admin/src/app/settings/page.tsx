import Link from "next/link";
import { ProtectedRoute, SettingsIcon } from "@securecy/ui";

export default function SettingsPage() {
  return (
    <ProtectedRoute requiredPermissions={["settings.manage"]}>
      <div className="mx-auto max-w-3xl space-y-6">
        <div>
          <h1 className="text-h2 font-bold text-night-900">Settings</h1>
          <p className="mt-1 text-body-md text-neutral-500">
            Manage organization preferences and operational defaults.
          </p>
        </div>

        <div className="divide-y divide-neutral-100 overflow-hidden rounded-card border border-neutral-200 bg-white">
          <Link
            href="/settings/notification-templates"
            className="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-primary-50"
          >
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600">
              <SettingsIcon className="h-5 w-5" />
            </span>
            <div>
              <p className="text-body-md font-semibold text-night-900">Notification Templates</p>
              <p className="text-body-sm text-neutral-500">
                Customize email and in-app notification content for your tenant.
              </p>
            </div>
          </Link>
        </div>
      </div>
    </ProtectedRoute>
  );
}
