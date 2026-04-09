"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import type {
  CertificateTemplate,
  CertificateTemplateLayout,
  CertificateTemplateStatus,
  CreateCertificateTemplatePayload,
  UpdateCertificateTemplatePayload,
} from "@securecy/types";
import { useRouter } from "next/navigation";
import { useEffect, useMemo, useRef, useState } from "react";

import {
  Alert,
  Button,
  Card,
  Input,
  Label,
  ProtectedRoute,
  Select,
  isApiClientError,
  useToast,
} from "@securecy/ui";

import {
  createCertificateTemplate,
  downloadTemplatePreview,
  fetchCertificateTemplate,
  updateCertificateTemplate,
} from "@/lib/certificates";

import { PlaceholderInserter } from "./placeholder-inserter";

interface CertificateTemplateFormPageProps {
  templateId?: number;
}

const layoutOptions = [
  { label: "Landscape", value: "landscape" },
  { label: "Portrait", value: "portrait" },
];

const statusOptions = [
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" },
];

const sampleValues: Record<string, string> = {
  learner_name: "Avery Carter",
  course_title: "Applied Security Foundations",
  completion_date: "April 9, 2026",
  certificate_id: "PREVIEW-2026-001",
  verification_code: "SCY-PREV-26A1",
};

const defaultTemplateContent = `<div style="padding: 64px; text-align: center;">
  <p style="letter-spacing: 0.32em; text-transform: uppercase; color: #5b92c6;">Securecy</p>
  <h1 style="font-size: 44px; margin-top: 24px;">Certificate of Completion</h1>
  <p style="font-size: 18px; margin-top: 28px;">This certifies that</p>
  <h2 style="font-size: 34px; margin-top: 18px;">{{learner_name}}</h2>
  <p style="font-size: 18px; margin-top: 24px;">has successfully completed</p>
  <h3 style="font-size: 30px; margin-top: 18px;">{{course_title}}</h3>
  <p style="font-size: 16px; margin-top: 24px;">Completed on {{completion_date}}</p>
  <div style="margin-top: 36px; display: flex; justify-content: space-between; gap: 16px;">
    <span style="font-size: 14px;">Certificate ID: {{certificate_id}}</span>
    <span style="font-size: 14px;">Verification Code: {{verification_code}}</span>
  </div>
</div>`;

export function CertificateTemplateFormPage({
  templateId,
}: CertificateTemplateFormPageProps) {
  const router = useRouter();
  const { showToast } = useToast();
  const editorRef = useRef<HTMLTextAreaElement>(null);
  const [isLoading, setIsLoading] = useState(Boolean(templateId));
  const [isSaving, setIsSaving] = useState(false);
  const [isPreviewing, setIsPreviewing] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [layout, setLayout] = useState<CertificateTemplateLayout>("landscape");
  const [status, setStatus] = useState<CertificateTemplateStatus>("active");
  const [isDefault, setIsDefault] = useState(false);
  const [contentHtml, setContentHtml] = useState(defaultTemplateContent);
  const [backgroundImageFile, setBackgroundImageFile] = useState<File | null>(null);
  const [backgroundImageUrl, setBackgroundImageUrl] = useState<string | null>(null);
  const [clearBackgroundImage, setClearBackgroundImage] = useState(false);
  const [loadedTemplate, setLoadedTemplate] = useState<CertificateTemplate | null>(null);

  useEffect(() => {
    if (!templateId) {
      return;
    }

    let cancelled = false;

    setIsLoading(true);

    fetchCertificateTemplate(templateId)
      .then((template) => {
        if (cancelled) {
          return;
        }

        setLoadedTemplate(template);
        setName(template.name);
        setDescription(template.description ?? "");
        setLayout(template.layout);
        setStatus(template.status);
        setIsDefault(template.is_default);
        setContentHtml(template.content_html);
        setBackgroundImageUrl(template.background_image_url);
      })
      .catch(() => {
        if (!cancelled) {
          setGeneralError("The certificate template could not be loaded.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [templateId]);

  useEffect(() => {
    return () => {
      if (backgroundImageUrl?.startsWith("blob:")) {
        URL.revokeObjectURL(backgroundImageUrl);
      }
    };
  }, [backgroundImageUrl]);

  const previewHtml = useMemo(() => renderPreviewContent(contentHtml), [contentHtml]);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setGeneralError(null);
    setFieldErrors({});
    setIsSaving(true);

    try {
      const payload = {
        name,
        description: description || "",
        layout,
        content_html: contentHtml,
        is_default: isDefault,
        status,
        clear_background_image: clearBackgroundImage,
      } satisfies UpdateCertificateTemplatePayload;

      let template: CertificateTemplate;

      if (templateId) {
        template = await updateCertificateTemplate(templateId, payload, backgroundImageFile);
      } else {
        template = await createCertificateTemplate(
          payload as CreateCertificateTemplatePayload,
          backgroundImageFile,
        );
      }

      showToast({
        tone: "success",
        title: templateId ? "Template updated" : "Template created",
        message: `${template.name} has been saved.`,
      });

      router.push(`/certificates/templates/${template.id}/edit`);
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "The template could not be saved.");
      } else {
        setGeneralError("The template could not be saved.");
      }
    } finally {
      setIsSaving(false);
    }
  }

  async function handlePreviewPdf() {
    if (!templateId) {
      showToast({
        tone: "info",
        title: "Save first",
        message: "Save the template once before opening the generated PDF preview.",
      });
      return;
    }

    setIsPreviewing(true);

    try {
      const blob = await downloadTemplatePreview(templateId);
      const previewUrl = URL.createObjectURL(blob);
      window.open(previewUrl, "_blank", "noopener,noreferrer");
      window.setTimeout(() => URL.revokeObjectURL(previewUrl), 60_000);
    } catch {
      showToast({
        tone: "error",
        title: "Preview unavailable",
        message: "The PDF preview could not be generated.",
      });
    } finally {
      setIsPreviewing(false);
    }
  }

  function handleInsertPlaceholder(placeholder: string) {
    const editor = editorRef.current;

    if (!editor) {
      setContentHtml((current) => `${current}${placeholder}`);
      return;
    }

    const start = editor.selectionStart;
    const end = editor.selectionEnd;

    setContentHtml((current) => `${current.slice(0, start)}${placeholder}${current.slice(end)}`);

    window.requestAnimationFrame(() => {
      const nextPosition = start + placeholder.length;
      editor.focus();
      editor.setSelectionRange(nextPosition, nextPosition);
    });
  }

  function handleBackgroundChange(file: File | null) {
    if (backgroundImageUrl?.startsWith("blob:")) {
      URL.revokeObjectURL(backgroundImageUrl);
    }

    if (!file) {
      setBackgroundImageFile(null);
      setBackgroundImageUrl(loadedTemplate?.background_image_url ?? null);
      return;
    }

    setClearBackgroundImage(false);
    setBackgroundImageFile(file);
    setBackgroundImageUrl(URL.createObjectURL(file));
  }

  if (isLoading) {
    return (
      <ProtectedRoute requiredPermissions={["certificates.issue"]}>
        <div className="mx-auto max-w-7xl px-6 py-8">
          <div className="h-12 animate-pulse rounded bg-neutral-100" />
          <div className="mt-6 h-[640px] animate-pulse rounded-card bg-neutral-100" />
        </div>
      </ProtectedRoute>
    );
  }

  return (
    <ProtectedRoute requiredPermissions={["certificates.issue"]}>
      <div className="mx-auto max-w-[1500px] px-6 py-8">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p className="text-overline uppercase tracking-[0.18em] text-primary-700">Certificates</p>
            <h1 className="mt-2 text-h1 text-night-900">
              {templateId ? "Edit Template" : "Create Template"}
            </h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Build the HTML certificate layout, manage defaults, and preview the generated PDF.
            </p>
          </div>

          <div className="flex flex-wrap gap-3">
            <Button type="button" variant="secondary" onClick={() => router.push("/certificates/templates")}>
              Back to Templates
            </Button>
            <Button type="button" variant="secondary" disabled={isPreviewing} onClick={() => void handlePreviewPdf()}>
              {isPreviewing ? "Opening..." : "Open PDF Preview"}
            </Button>
          </div>
        </div>

        <form className="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(420px,0.9fr)]" onSubmit={handleSubmit}>
          <Card>
            {generalError ? <Alert tone="error">{generalError}</Alert> : null}

            <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="template-name">Template name</Label>
                <Input
                  id="template-name"
                  value={name}
                  error={Boolean(fieldErrors.name)}
                  onChange={(event) => setName(event.target.value)}
                  placeholder="Completion certificate"
                />
                {fieldErrors.name ? <p className="text-body-sm text-error-500">{fieldErrors.name}</p> : null}
              </div>

              <div className="space-y-2">
                <Label htmlFor="template-layout">Layout</Label>
                <Select
                  id="template-layout"
                  value={layout}
                  options={layoutOptions}
                  onChange={(event) => setLayout(event.target.value as CertificateTemplateLayout)}
                />
              </div>
            </div>

            <div className="mt-5 space-y-2">
              <Label htmlFor="template-description">Description</Label>
              <Input
                id="template-description"
                value={description}
                onChange={(event) => setDescription(event.target.value)}
                placeholder="Used for standard learner completions"
              />
            </div>

            <div className="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="template-status">Status</Label>
                <Select
                  id="template-status"
                  value={status}
                  options={statusOptions}
                  onChange={(event) => setStatus(event.target.value as CertificateTemplateStatus)}
                />
              </div>

              <div className="rounded-3xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                <label className="flex items-start gap-3">
                  <input
                    type="checkbox"
                    className="mt-1 h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                    checked={isDefault}
                    onChange={(event) => setIsDefault(event.target.checked)}
                  />
                  <span>
                    <span className="block text-body-md font-semibold text-night-900">Set as tenant default</span>
                    <span className="mt-1 block text-body-sm text-neutral-500">
                      New course completions will use this template when no course-specific template is selected.
                    </span>
                  </span>
                </label>
              </div>
            </div>

            <div className="mt-5 space-y-3">
              <Label htmlFor="template-background">Background image</Label>
              <div className="flex flex-wrap items-center gap-3">
                <Input
                  id="template-background"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  onChange={(event) => handleBackgroundChange(event.target.files?.[0] ?? null)}
                />
                {(backgroundImageUrl || loadedTemplate?.background_image_url) ? (
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      setClearBackgroundImage(true);
                      setBackgroundImageFile(null);
                      setBackgroundImageUrl(null);
                    }}
                  >
                    Remove background
                  </Button>
                ) : null}
              </div>
              {fieldErrors.background_image ? (
                <p className="text-body-sm text-error-500">{fieldErrors.background_image}</p>
              ) : null}
              {backgroundImageUrl ? (
                <div className="overflow-hidden rounded-3xl border border-neutral-200 bg-white">
                  <img src={backgroundImageUrl} alt="Certificate background preview" className="h-40 w-full object-cover" />
                </div>
              ) : null}
            </div>

            <div className="mt-6 space-y-3">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <Label htmlFor="template-content">Template HTML</Label>
                <PlaceholderInserter onInsert={handleInsertPlaceholder} />
              </div>
              <textarea
                id="template-content"
                ref={editorRef}
                value={contentHtml}
                onChange={(event) => setContentHtml(event.target.value)}
                rows={24}
                className="w-full rounded-[28px] border border-neutral-300 px-4 py-4 font-mono text-body-sm leading-6 text-night-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              />
              {fieldErrors.content_html ? (
                <p className="text-body-sm text-error-500">{fieldErrors.content_html}</p>
              ) : null}
            </div>

            <div className="mt-6 flex flex-wrap justify-end gap-3">
              <Button type="button" variant="secondary" onClick={() => router.push("/certificates/templates")}>
                Cancel
              </Button>
              <Button type="submit" disabled={isSaving}>
                {isSaving ? "Saving..." : "Save Template"}
              </Button>
            </div>
          </Card>

          <Card className="overflow-hidden">
            <div className="flex items-center justify-between gap-3 border-b border-neutral-200 pb-4">
              <div>
                <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">Live Preview</p>
                <h2 className="mt-2 text-h3 text-night-900">Rendered sample certificate</h2>
              </div>
              <span className="rounded-full bg-primary-50 px-3 py-1 text-body-sm font-semibold text-primary-700">
                {layout}
              </span>
            </div>

            <div className="mt-6 rounded-[32px] border border-primary-100 bg-primary-50 p-4">
              <div
                className={`mx-auto overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-card ${
                  layout === "landscape" ? "aspect-[1.414/1]" : "aspect-[0.77/1]"
                }`}
                style={{
                  backgroundImage: backgroundImageUrl ? `url(${backgroundImageUrl})` : undefined,
                  backgroundSize: "cover",
                  backgroundPosition: "center",
                }}
              >
                <div
                  className="h-full w-full bg-white/80 p-8 text-[14px] leading-6 text-night-900"
                  dangerouslySetInnerHTML={{ __html: previewHtml }}
                />
              </div>
            </div>
          </Card>
        </form>
      </div>
    </ProtectedRoute>
  );
}

function renderPreviewContent(value: string): string {
  return value.replace(
    /{{\s*(learner_name|course_title|completion_date|certificate_id|verification_code)\s*}}/g,
    (_, key: keyof typeof sampleValues) => sampleValues[key],
  );
}
