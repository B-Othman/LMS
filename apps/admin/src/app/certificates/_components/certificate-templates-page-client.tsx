"use client";

import type { CertificateTemplate } from "@securecy/types";
import Link from "next/link";
import { useEffect, useState } from "react";

import {
  Badge,
  Button,
  DataTable,
  Modal,
  ProtectedRoute,
  useToast,
  type DataTableColumn,
} from "@securecy/ui";

import {
  deleteCertificateTemplate,
  downloadTemplatePreview,
  fetchCertificateTemplates,
} from "@/lib/certificates";

export function CertificateTemplatesPageClient() {
  const { showToast } = useToast();
  const [templates, setTemplates] = useState<CertificateTemplate[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [deleteTarget, setDeleteTarget] = useState<CertificateTemplate | null>(null);

  useEffect(() => {
    let cancelled = false;

    fetchCertificateTemplates()
      .then((rows) => {
        if (!cancelled) {
          setTemplates(rows);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setTemplates([]);
          showToast({
            tone: "error",
            title: "Templates unavailable",
            message: "The certificate template list could not be loaded.",
          });
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
  }, [showToast]);

  const columns: DataTableColumn<CertificateTemplate>[] = [
    {
      key: "name",
      header: "Template",
      render: (row) => (
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <p className="truncate text-body-md font-semibold text-night-900">{row.name}</p>
            {row.is_default ? <Badge variant="info">Default</Badge> : null}
          </div>
          <p className="truncate text-body-sm text-neutral-500">{row.description ?? "No description"}</p>
        </div>
      ),
    },
    {
      key: "layout",
      header: "Layout",
      render: (row) => <span className="text-body-sm text-neutral-600">{capitalize(row.layout)}</span>,
    },
    {
      key: "status",
      header: "Status",
      render: (row) => (
        <Badge variant={row.status === "active" ? "success" : "neutral"}>
          {capitalize(row.status)}
        </Badge>
      ),
    },
    {
      key: "issued_count",
      header: "Issued",
      render: (row) => <span className="text-body-sm text-neutral-600">{row.issued_count}</span>,
    },
    {
      key: "actions",
      header: "Actions",
      cellClassName: "w-[280px]",
      render: (row) => (
        <div className="flex flex-wrap gap-3">
          <Link href={`/certificates/templates/${row.id}/edit`} className="text-body-sm font-semibold text-primary-700 underline">
            Edit
          </Link>
          <button
            type="button"
            className="text-body-sm font-semibold text-neutral-700 underline"
            onClick={() => void handlePreview(row.id)}
          >
            Preview PDF
          </button>
          <button
            type="button"
            className={`text-body-sm font-semibold underline ${
              row.issued_count > 0 ? "cursor-not-allowed text-neutral-300" : "text-error-600"
            }`}
            disabled={row.issued_count > 0}
            onClick={() => setDeleteTarget(row)}
          >
            Delete
          </button>
        </div>
      ),
    },
  ];

  async function handlePreview(templateId: number) {
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
    }
  }

  async function confirmDelete() {
    if (!deleteTarget) {
      return;
    }

    try {
      await deleteCertificateTemplate(deleteTarget.id);
      setTemplates((current) => current.filter((template) => template.id !== deleteTarget.id));
      showToast({
        tone: "success",
        title: "Template deleted",
        message: `${deleteTarget.name} has been removed.`,
      });
      setDeleteTarget(null);
    } catch {
      showToast({
        tone: "error",
        title: "Delete failed",
        message: "Templates that already issued certificates cannot be deleted.",
      });
    }
  }

  return (
    <ProtectedRoute requiredPermissions={["certificates.issue"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Certificate Templates</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Manage reusable certificate designs, defaults, and PDF previews.
            </p>
          </div>

          <div className="flex flex-wrap gap-3">
            <Link href="/certificates" className="inline-flex items-center justify-center rounded-lg border border-neutral-300 bg-neutral-100 px-4 py-2 text-button text-neutral-700 transition-colors hover:bg-neutral-200">
              View Issued Certificates
            </Link>
            <Button type="button" onClick={() => window.location.assign("/certificates/templates/create")}>
              Create Template
            </Button>
          </div>
        </div>

        <div className="mt-8">
          <DataTable
            columns={columns}
            rows={templates}
            rowKey={(row) => row.id}
            emptyState={isLoading ? "Loading templates..." : "No certificate templates have been created yet."}
          />
        </div>
      </div>

      <Modal
        open={deleteTarget !== null}
        onClose={() => setDeleteTarget(null)}
        title="Delete template"
        description="This removes the template if it has never been used to issue a certificate."
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
            <Button type="button" variant="danger" onClick={() => void confirmDelete()}>
              Delete Template
            </Button>
          </>
        }
      >
        <p className="text-body-md text-neutral-600">
          {deleteTarget ? `Delete ${deleteTarget.name}?` : ""}
        </p>
      </Modal>
    </ProtectedRoute>
  );
}

function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}
