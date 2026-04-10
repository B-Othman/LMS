"use client";

import type { NotificationPreference } from "@securecy/types";
import { useEffect, useState } from "react";
import { Button, useToast } from "@securecy/ui";
import {
  fetchMyNotificationPreferences,
  updateMyNotificationPreferences,
} from "@/lib/notifications";

function ToggleSwitch({
  checked,
  onChange,
  label,
}: {
  checked: boolean;
  onChange: (value: boolean) => void;
  label: string;
}) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      aria-label={label}
      onClick={() => onChange(!checked)}
      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 ${
        checked ? "bg-primary-500" : "bg-neutral-300"
      }`}
    >
      <span
        className={`inline-block h-4 w-4 rounded-full bg-white shadow transition-transform ${
          checked ? "translate-x-6" : "translate-x-1"
        }`}
      />
    </button>
  );
}

export function LearnerNotificationSettingsPage() {
  const { showToast } = useToast();
  const [preferences, setPreferences] = useState<NotificationPreference[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    fetchMyNotificationPreferences()
      .then(setPreferences)
      .catch(() => showToast({ tone: "error", message: "Failed to load preferences." }))
      .finally(() => setIsLoading(false));
  }, [showToast]);

  function updatePref(type: string, field: "email_enabled" | "in_app_enabled", value: boolean) {
    setPreferences((prev) =>
      prev.map((p) => (p.type === type ? { ...p, [field]: value } : p)),
    );
  }

  async function handleSave() {
    setIsSaving(true);
    try {
      const updated = await updateMyNotificationPreferences({
        preferences: preferences.map(({ type, email_enabled, in_app_enabled }) => ({
          type,
          email_enabled,
          in_app_enabled,
        })),
      });
      setPreferences(updated);
      showToast({ tone: "success", message: "Notification preferences saved." });
    } catch {
      showToast({ tone: "error", message: "Failed to save preferences." });
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="text-h2 font-bold text-night-900">Notification Preferences</h1>
        <p className="mt-1 text-body-md text-neutral-500">
          Choose which notifications you receive and through which channels.
        </p>
      </div>

      <div className="overflow-hidden rounded-card border border-neutral-200 bg-white">
        {/* Header */}
        <div className="grid grid-cols-[1fr_80px_80px] gap-4 border-b border-neutral-100 px-5 py-3">
          <span className="text-body-sm font-semibold text-neutral-500 uppercase tracking-wide">
            Notification type
          </span>
          <span className="text-center text-body-sm font-semibold text-neutral-500 uppercase tracking-wide">
            Email
          </span>
          <span className="text-center text-body-sm font-semibold text-neutral-500 uppercase tracking-wide">
            In-App
          </span>
        </div>

        {isLoading ? (
          <div className="divide-y divide-neutral-100">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="grid grid-cols-[1fr_80px_80px] items-center gap-4 px-5 py-4">
                <div className="h-4 w-40 animate-pulse rounded bg-neutral-100" />
                <div className="flex justify-center">
                  <div className="h-6 w-11 animate-pulse rounded-full bg-neutral-100" />
                </div>
                <div className="flex justify-center">
                  <div className="h-6 w-11 animate-pulse rounded-full bg-neutral-100" />
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="divide-y divide-neutral-100">
            {preferences.map((pref) => (
              <div
                key={pref.type}
                className="grid grid-cols-[1fr_80px_80px] items-center gap-4 px-5 py-4"
              >
                <span className="text-body-md text-night-800">{pref.label}</span>
                <div className="flex justify-center">
                  <ToggleSwitch
                    checked={pref.email_enabled}
                    onChange={(v) => updatePref(pref.type, "email_enabled", v)}
                    label={`Email notifications for ${pref.label}`}
                  />
                </div>
                <div className="flex justify-center">
                  <ToggleSwitch
                    checked={pref.in_app_enabled}
                    onChange={(v) => updatePref(pref.type, "in_app_enabled", v)}
                    label={`In-app notifications for ${pref.label}`}
                  />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="flex justify-end">
        <Button onClick={() => void handleSave()} disabled={isSaving || isLoading}>
          {isSaving ? "Saving…" : "Save preferences"}
        </Button>
      </div>
    </div>
  );
}
