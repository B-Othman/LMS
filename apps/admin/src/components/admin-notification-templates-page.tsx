"use client";

import type { NotificationChannel, NotificationTemplate, UpdateNotificationTemplatePayload } from "@securecy/types";
import { useEffect, useState } from "react";
import {
  Badge,
  Button,
  DataTable,
  type DataTableColumn,
  Modal,
  Select,
  useToast,
} from "@securecy/ui";
import {
  fetchNotificationTemplates,
  resetNotificationTemplate,
  updateNotificationTemplate,
} from "@/lib/notifications";

const CHANNEL_OPTIONS = [
  { value: "email", label: "Email only" },
  { value: "in_app", label: "In-app only" },
  { value: "both", label: "Both (Email + In-app)" },
];

const TYPE_LABELS: Record<string, string> = {
  enrollment_created: "Course Enrollment",
  course_completed: "Course Completed",
  certificate_issued: "Certificate Issued",
  quiz_failed: "Quiz Failed",
  welcome: "Welcome",
  enrollment_reminder: "Enrollment Reminder",
  course_due_soon: "Course Due Soon",
};

const CHANNEL_LABELS: Record<NotificationChannel, string> = {
  email: "Email",
  in_app: "In-App",
  both: "Both",
};

const SAMPLE_DATA: Record<string, string> = {
  user_name: "Jane Smith",
  course_title: "Introduction to Security Awareness",
  due_date: "April 30, 2026",
  days_remaining: "5",
  completed_date: "April 9, 2026",
  issued_date: "April 9, 2026",
  download_url: "https://app.securecy.com/certificates/42/download",
  verification_code: "CERT-2026-XYZ",
  quiz_title: "Final Assessment",
  score: "58",
  pass_score: "70",
  login_url: "https://app.securecy.com/login",
};

function renderPreview(template: string): string {
  let result = template;
  for (const [key, value] of Object.entries(SAMPLE_DATA)) {
    result = result.replaceAll(`{{${key}}}`, value);
  }
  return result;
}

export function AdminNotificationTemplatesPage() {
  const { showToast } = useToast();
  const [templates, setTemplates] = useState<NotificationTemplate[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [editingTemplate, setEditingTemplate] = useState<NotificationTemplate | null>(null);
  const [previewMode, setPreviewMode] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  const [form, setForm] = useState<UpdateNotificationTemplatePayload>({
    subject_template: "",
    body_html_template: "",
    body_text_template: "",
    channel: "both",
    is_active: true,
  });

  useEffect(() => {
    fetchNotificationTemplates()
      .then(setTemplates)
      .catch(() => showToast({ tone: "error", message: "Failed to load templates." }))
      .finally(() => setIsLoading(false));
  }, [showToast]);

  function openEdit(template: NotificationTemplate) {
    setEditingTemplate(template);
    setPreviewMode(false);
    setForm({
      subject_template: template.subject_template,
      body_html_template: template.body_html_template,
      body_text_template: template.body_text_template,
      channel: template.channel,
      is_active: template.is_active,
    });
  }

  async function handleSave() {
    if (!editingTemplate) return;
    setIsSaving(true);
    try {
      const updated = await updateNotificationTemplate(editingTemplate.id, form);
      setTemplates((prev) =>
        prev.map((t) => (t.type === updated.type ? { ...t, ...form, is_tenant_override: true } : t)),
      );
      setEditingTemplate(null);
      showToast({ tone: "success", message: "Template updated." });
    } catch {
      showToast({ tone: "error", message: "Failed to update template." });
    } finally {
      setIsSaving(false);
    }
  }

  async function handleReset(template: NotificationTemplate) {
    try {
      const reset = await resetNotificationTemplate(template.id);
      setTemplates((prev) => prev.map((t) => (t.type === reset.type ? reset : t)));
      showToast({ tone: "success", message: "Template reset to system default." });
    } catch {
      showToast({ tone: "error", message: "Failed to reset template." });
    }
  }

  const columns: DataTableColumn<NotificationTemplate>[] = [
    {
      key: "type",
      header: "Type",
      render: (t) => <span className="font-medium text-night-900">{TYPE_LABELS[t.type] ?? t.type}</span>,
    },
    {
      key: "subject_template",
      header: "Subject",
      render: (t) => <span className="text-body-sm text-neutral-600 line-clamp-1">{t.subject_template}</span>,
    },
    {
      key: "channel",
      header: "Channel",
      render: (t) => <Badge variant="info">{CHANNEL_LABELS[t.channel]}</Badge>,
    },
    {
      key: "is_active",
      header: "Status",
      render: (t) => (
        <Badge variant={t.is_active ? "success" : "neutral"}>
          {t.is_active ? "Active" : "Inactive"}
        </Badge>
      ),
    },
    {
      key: "is_tenant_override",
      header: "Source",
      render: (t) => (
        <Badge variant={t.is_tenant_override ? "info" : "neutral"}>
          {t.is_tenant_override ? "Custom" : "Default"}
        </Badge>
      ),
    },
    {
      key: "actions",
      header: "",
      render: (t) => (
        <div className="flex justify-end gap-2">
          <Button size="sm" variant="secondary" onClick={() => openEdit(t)}>
            Edit
          </Button>
          {t.is_tenant_override ? (
            <button
              type="button"
              onClick={() => void handleReset(t)}
              className="rounded-lg px-3 py-1.5 text-body-sm font-medium text-neutral-600 hover:bg-neutral-100"
            >
              Reset
            </button>
          ) : null}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-h2 font-bold text-night-900">Notification Templates</h1>
        <p className="mt-1 text-body-md text-neutral-500">
          Customize email and in-app notification content for your tenant.
        </p>
      </div>

      {isLoading ? (
        <div className="space-y-3">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-14 animate-pulse rounded-card bg-neutral-100" />
          ))}
        </div>
      ) : (
        <DataTable<NotificationTemplate>
          columns={columns}
          rows={templates}
          rowKey={(t) => t.id}
        />
      )}

      <Modal
        open={editingTemplate !== null}
        title={editingTemplate ? `Edit: ${TYPE_LABELS[editingTemplate.type] ?? editingTemplate.type}` : "Edit Template"}
        onClose={() => setEditingTemplate(null)}
        size="lg"
      >
        <div className="space-y-4">
          {/* Preview toggle */}
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setPreviewMode(false)}
              className={`rounded-md px-3 py-1.5 text-body-sm font-medium ${!previewMode ? "bg-primary-100 text-primary-700" : "text-neutral-500 hover:text-night-900"}`}
            >
              Edit
            </button>
            <button
              type="button"
              onClick={() => setPreviewMode(true)}
              className={`rounded-md px-3 py-1.5 text-body-sm font-medium ${previewMode ? "bg-primary-100 text-primary-700" : "text-neutral-500 hover:text-night-900"}`}
            >
              Preview
            </button>
          </div>

          {previewMode ? (
            <div className="space-y-3">
              <div className="rounded-card border border-neutral-200 p-4">
                <p className="mb-1 text-body-sm font-semibold text-neutral-500">Subject</p>
                <p className="text-body-md text-night-900">{renderPreview(form.subject_template)}</p>
              </div>
              <div className="rounded-card border border-neutral-200 p-4">
                <p className="mb-2 text-body-sm font-semibold text-neutral-500">HTML Body</p>
                <div
                  className="text-body-sm [&_a]:text-primary-600 [&_a]:underline [&_strong]:font-semibold"
                  dangerouslySetInnerHTML={{ __html: renderPreview(form.body_html_template) }}
                />
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-body-sm font-semibold text-night-800">
                  Subject template
                </label>
                <input
                  type="text"
                  value={form.subject_template}
                  onChange={(e) => setForm((f) => ({ ...f, subject_template: e.target.value }))}
                  className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-body-sm text-night-900 focus:outline-none focus:ring-2 focus:ring-primary-400"
                  placeholder="e.g. You have been enrolled in {{course_title}}"
                />
                <p className="mt-1 text-body-sm text-neutral-400">
                  {'Available: {{user_name}}, {{course_title}}, {{due_date}}, {{score}}, {{pass_score}}, {{login_url}}'}
                </p>
              </div>

              <div>
                <label className="mb-1 block text-body-sm font-semibold text-night-800">
                  HTML body
                </label>
                <textarea
                  value={form.body_html_template}
                  onChange={(e) => setForm((f) => ({ ...f, body_html_template: e.target.value }))}
                  rows={8}
                  className="w-full rounded-lg border border-neutral-300 px-3 py-2 font-mono text-body-sm text-night-900 focus:outline-none focus:ring-2 focus:ring-primary-400"
                />
              </div>

              <div>
                <label className="mb-1 block text-body-sm font-semibold text-night-800">
                  Plain text body
                </label>
                <textarea
                  value={form.body_text_template}
                  onChange={(e) => setForm((f) => ({ ...f, body_text_template: e.target.value }))}
                  rows={4}
                  className="w-full rounded-lg border border-neutral-300 px-3 py-2 font-mono text-body-sm text-night-900 focus:outline-none focus:ring-2 focus:ring-primary-400"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="mb-1 block text-body-sm font-semibold text-night-800">
                    Channel
                  </label>
                  <Select
                    options={CHANNEL_OPTIONS}
                    value={form.channel}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, channel: e.target.value as NotificationChannel }))
                    }
                  />
                </div>
                <div className="flex items-end">
                  <label className="flex cursor-pointer items-center gap-3">
                    <input
                      type="checkbox"
                      checked={form.is_active}
                      onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
                      className="h-4 w-4 rounded border-neutral-300 text-primary-500 focus:ring-primary-400"
                    />
                    <span className="text-body-sm font-medium text-night-800">Active</span>
                  </label>
                </div>
              </div>
            </div>
          )}

          <div className="flex justify-end gap-3 border-t border-neutral-100 pt-4">
            <Button variant="secondary" onClick={() => setEditingTemplate(null)}>
              Cancel
            </Button>
            <Button onClick={() => void handleSave()} disabled={isSaving}>
              {isSaving ? "Saving…" : "Save template"}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
