"use client";

import type { Certificate, CertificateListFilters, CertificateStatus, Course } from "@securecy/types";
import Link from "next/link";
import { useDeferredValue, useEffect, useMemo, useState } from "react";

import {
  Badge,
  Button,
  DataTable,
  Input,
  Modal,
  Pagination,
  ProtectedRoute,
  Select,
  useToast,
  type DataTableColumn,
} from "@securecy/ui";

import { api } from "@/lib/api";
import {
  downloadCertificate,
  fetchIssuedCertificates,
  revokeCertificate,
} from "@/lib/certificates";

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const emptyMeta: PaginationMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
};

const statusOptions = [
  { label: "All statuses", value: "" },
  { label: "Active", value: "active" },
  { label: "Expired", value: "expired" },
  { label: "Revoked", value: "revoked" },
];

export function CertificatesPageClient() {
  const { showToast } = useToast();
  const [rows, setRows] = useState<Certificate[]>([]);
  const [courses, setCourses] = useState<Course[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(emptyMeta);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState("");
  const deferredSearch = useDeferredValue(search);
  const [courseId, setCourseId] = useState("");
  const [status, setStatus] = useState<CertificateStatus | "">("");
  const [issuedFrom, setIssuedFrom] = useState("");
  const [issuedTo, setIssuedTo] = useState("");
  const [page, setPage] = useState(1);
  const [revokeTarget, setRevokeTarget] = useState<Certificate | null>(null);
  const [revokeReason, setRevokeReason] = useState("");

  useEffect(() => {
    let cancelled = false;

    api
      .paginated<Course>("/courses", {
        params: {
          per_page: 100,
          sort_by: "title",
          sort_dir: "asc",
        },
      })
      .then((response) => {
        if (!cancelled) {
          setCourses(response.data ?? []);
        }
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    let cancelled = false;

    setIsLoading(true);

    fetchIssuedCertificates({
      search: deferredSearch || undefined,
      course_id: courseId ? Number(courseId) : undefined,
      status: status || undefined,
      issued_from: issuedFrom || undefined,
      issued_to: issuedTo || undefined,
      page,
      per_page: emptyMeta.per_page,
    } satisfies CertificateListFilters)
      .then((response) => {
        if (cancelled) {
          return;
        }

        setRows(response.data ?? []);
        setMeta((response.meta as PaginationMeta | undefined) ?? emptyMeta);
      })
      .catch(() => {
        if (!cancelled) {
          setRows([]);
          setMeta(emptyMeta);
          showToast({
            tone: "error",
            title: "Certificates unavailable",
            message: "The issued certificate list could not be loaded.",
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
  }, [courseId, deferredSearch, issuedFrom, issuedTo, page, showToast, status]);

  const courseOptions = useMemo(
    () => [
      { label: "All courses", value: "" },
      ...courses.map((course) => ({
        label: course.title,
        value: String(course.id),
      })),
    ],
    [courses],
  );

  const columns: DataTableColumn<Certificate>[] = [
    {
      key: "learner",
      header: "Learner",
      render: (row) => (
        <div className="min-w-0">
          <p className="truncate text-body-md font-semibold text-night-900">{row.user?.full_name ?? "Unknown learner"}</p>
          <p className="truncate text-body-sm text-neutral-500">{row.user?.email ?? "No email"}</p>
        </div>
      ),
    },
    {
      key: "course",
      header: "Course",
      render: (row) => (
        <div className="min-w-0">
          <p className="truncate text-body-md font-semibold text-night-900">{row.course?.title ?? "Unknown course"}</p>
          <p className="text-body-sm text-neutral-500">Code: {row.verification_code}</p>
        </div>
      ),
    },
    {
      key: "issued_at",
      header: "Issued",
      render: (row) => <span className="text-body-sm text-neutral-600">{formatDate(row.issued_at)}</span>,
    },
    {
      key: "expires_at",
      header: "Expires",
      render: (row) => <span className="text-body-sm text-neutral-600">{formatDate(row.expires_at)}</span>,
    },
    {
      key: "status",
      header: "Status",
      render: (row) => <CertificateStatusBadge status={row.status} />,
    },
    {
      key: "actions",
      header: "Actions",
      cellClassName: "w-[220px]",
      render: (row) => (
        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            disabled={!row.file_ready}
            className={`text-body-sm font-semibold underline ${row.file_ready ? "text-primary-700" : "cursor-not-allowed text-neutral-300"}`}
            onClick={() => void handleDownload(row.id)}
          >
            Download
          </button>
          {row.status !== "revoked" ? (
            <button
              type="button"
              className="text-body-sm font-semibold text-error-600 underline"
              onClick={() => {
                setRevokeTarget(row);
                setRevokeReason("");
              }}
            >
              Revoke
            </button>
          ) : null}
        </div>
      ),
    },
  ];

  async function handleDownload(id: number) {
    try {
      const download = await downloadCertificate(id);
      window.open(download.url, "_blank", "noopener,noreferrer");
    } catch {
      showToast({
        tone: "error",
        title: "Download unavailable",
        message: "The certificate PDF is not ready yet.",
      });
    }
  }

  async function confirmRevoke() {
    if (!revokeTarget || !revokeReason.trim()) {
      return;
    }

    try {
      const updated = await revokeCertificate(revokeTarget.id, { reason: revokeReason.trim() });
      setRows((current) => current.map((row) => (row.id === updated.id ? updated : row)));
      setRevokeTarget(null);
      setRevokeReason("");
      showToast({
        tone: "success",
        title: "Certificate revoked",
        message: "The certificate status was updated successfully.",
      });
    } catch {
      showToast({
        tone: "error",
        title: "Revoke failed",
        message: "The certificate could not be revoked.",
      });
    }
  }

  return (
    <ProtectedRoute requiredPermissions={["certificates.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Issued Certificates</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Track issued learner certificates, download PDFs, and revoke records when needed.
            </p>
          </div>

          <Link href="/certificates/templates" className="inline-flex items-center justify-center rounded-lg border border-neutral-300 bg-neutral-100 px-4 py-2 text-button text-neutral-700 transition-colors hover:bg-neutral-200">
            Manage Templates
          </Link>
        </div>

        <div className="mt-8 rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.2fr)_220px_200px_200px]">
            <div className="space-y-2">
              <label htmlFor="certificate-search" className="text-body-sm font-semibold text-night-800">
                Search learner
              </label>
              <Input
                id="certificate-search"
                value={search}
                onChange={(event) => {
                  setSearch(event.target.value);
                  setPage(1);
                }}
                placeholder="Search by learner name or email"
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="certificate-course" className="text-body-sm font-semibold text-night-800">
                Course
              </label>
              <Select
                id="certificate-course"
                value={courseId}
                options={courseOptions}
                onChange={(event) => {
                  setCourseId(event.target.value);
                  setPage(1);
                }}
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="certificate-status" className="text-body-sm font-semibold text-night-800">
                Status
              </label>
              <Select
                id="certificate-status"
                value={status}
                options={statusOptions}
                onChange={(event) => {
                  setStatus(event.target.value as CertificateStatus | "");
                  setPage(1);
                }}
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="certificate-issued-from" className="text-body-sm font-semibold text-night-800">
                Issued from
              </label>
              <Input
                id="certificate-issued-from"
                type="date"
                value={issuedFrom}
                onChange={(event) => {
                  setIssuedFrom(event.target.value);
                  setPage(1);
                }}
              />
            </div>
          </div>

          <div className="mt-4 grid grid-cols-1 gap-4 md:max-w-[260px]">
            <div className="space-y-2">
              <label htmlFor="certificate-issued-to" className="text-body-sm font-semibold text-night-800">
                Issued to
              </label>
              <Input
                id="certificate-issued-to"
                type="date"
                value={issuedTo}
                onChange={(event) => {
                  setIssuedTo(event.target.value);
                  setPage(1);
                }}
              />
            </div>
          </div>
        </div>

        <div className="mt-6">
          <DataTable
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            emptyState={isLoading ? "Loading certificates..." : "No certificates matched the current filters."}
          />
        </div>

        <div className="mt-6">
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            onPageChange={(nextPage) => setPage(nextPage)}
            disabled={isLoading}
          />
        </div>
      </div>

      <Modal
        open={revokeTarget !== null}
        onClose={() => setRevokeTarget(null)}
        title="Revoke certificate"
        description="Revoking keeps the record but marks the certificate as invalid for future verification."
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setRevokeTarget(null)}>
              Cancel
            </Button>
            <Button type="button" variant="danger" disabled={!revokeReason.trim()} onClick={() => void confirmRevoke()}>
              Revoke Certificate
            </Button>
          </>
        }
      >
        <div className="space-y-3">
          <p className="text-body-md text-neutral-600">
            {revokeTarget ? `Revoke ${revokeTarget.course?.title ?? "this certificate"} for ${revokeTarget.user?.full_name ?? "this learner"}?` : ""}
          </p>
          <label className="space-y-2">
            <span className="text-body-sm font-semibold text-night-800">Reason</span>
            <textarea
              value={revokeReason}
              onChange={(event) => setRevokeReason(event.target.value)}
              rows={4}
              className="w-full rounded-2xl border border-neutral-300 px-3 py-2 text-body-md text-night-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              placeholder="Explain why the certificate is being revoked"
            />
          </label>
        </div>
      </Modal>
    </ProtectedRoute>
  );
}

function CertificateStatusBadge({ status }: { status: CertificateStatus }) {
  const variant = status === "active" ? "success" : status === "expired" ? "warning" : "error";

  return <Badge variant={variant}>{capitalize(status)}</Badge>;
}

function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function formatDate(value: string | null): string {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
}
