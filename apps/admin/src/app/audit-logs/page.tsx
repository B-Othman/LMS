"use client";

import type { AuditLog } from "@securecy/types";
import { useCallback, useState } from "react";
import {
  Avatar,
  DataTable,
  type DataTableColumn,
  EmptyState,
  Input,
  ProtectedRoute,
  Select,
} from "@securecy/ui";
import { ACTION_OPTIONS, ActionBadge } from "@/components/action-badge";
import { AuditDiffViewer } from "@/components/audit-diff-viewer";
import { DateRangePicker, type DateRange } from "@/components/date-range-picker";
import { fetchAuditLogs } from "@/lib/audit";
import { useReport } from "@/app/reports/_components/use-report";

const ENTITY_TYPE_OPTIONS = [
  { value: "", label: "All entity types" },
  { value: "user", label: "User" },
  { value: "course", label: "Course" },
  { value: "enrollment", label: "Enrollment" },
  { value: "certificate", label: "Certificate" },
  { value: "quiz", label: "Quiz" },
  { value: "export", label: "Export" },
];

const ALL_ACTION_OPTIONS = [{ value: "", label: "All actions" }, ...ACTION_OPTIONS];

function formatDateTime(iso: string): string {
  return new Date(iso).toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default function AuditLogsPage() {
  const [search, setSearch] = useState("");
  const [action, setAction] = useState("");
  const [entityType, setEntityType] = useState("");
  const [dateRange, setDateRange] = useState<DateRange>({ from: "", to: "" });
  const [applied, setApplied] = useState({ search: "", action: "", entityType: "", dateRange: { from: "", to: "" } });
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const fetcher = useCallback(
    () =>
      fetchAuditLogs({
        search: applied.search || undefined,
        action: applied.action || undefined,
        entity_type: applied.entityType || undefined,
        date_from: applied.dateRange.from || undefined,
        date_to: applied.dateRange.to || undefined,
      }),
    [applied],
  );

  const { rows, meta, isLoading, page, setPage } = useReport(fetcher, [applied]);

  function applyFilters() {
    setApplied({ search, action, entityType, dateRange });
  }

  const columns: DataTableColumn<AuditLog>[] = [
    {
      key: "created_at",
      header: "Time",
      render: (r) => (
        <span className="whitespace-nowrap text-body-sm text-neutral-600">
          {formatDateTime(r.created_at)}
        </span>
      ),
    },
    {
      key: "actor",
      header: "Actor",
      render: (r) =>
        r.actor ? (
          <div className="flex items-center gap-2">
            <Avatar name={r.actor.name} size="sm" />
            <div className="min-w-0">
              <p className="truncate text-body-sm font-medium text-night-900">{r.actor.name}</p>
              <p className="truncate text-body-sm text-neutral-500">{r.actor.email}</p>
            </div>
          </div>
        ) : (
          <span className="text-body-sm text-neutral-400">System</span>
        ),
    },
    {
      key: "action",
      header: "Action",
      render: (r) => <ActionBadge action={r.action} />,
    },
    {
      key: "entity",
      header: "Entity",
      render: (r) =>
        r.entity_type ? (
          <span className="text-body-sm text-neutral-600 capitalize">
            {r.entity_type} #{r.entity_id}
          </span>
        ) : (
          <span className="text-neutral-300">—</span>
        ),
    },
    {
      key: "description",
      header: "Description",
      render: (r) => (
        <span className="text-body-sm text-night-800 line-clamp-1">{r.description}</span>
      ),
    },
    {
      key: "ip_address",
      header: "IP",
      render: (r) => (
        <span className="font-mono text-[11px] text-neutral-500">{r.ip_address ?? "—"}</span>
      ),
    },
    {
      key: "expand",
      header: "",
      render: (r) =>
        r.changes ? (
          <button
            type="button"
            onClick={() => setExpandedId(expandedId === r.id ? null : r.id)}
            className="text-body-sm font-medium text-primary-600 hover:underline whitespace-nowrap"
          >
            {expandedId === r.id ? "Hide diff" : "View diff"}
          </button>
        ) : null,
    },
  ];

  return (
    <ProtectedRoute requiredPermissions={["users.view"]}>
      <div className="mx-auto max-w-7xl space-y-6">
        <div>
          <h1 className="text-h2 font-bold text-night-900">Audit Log</h1>
          <p className="mt-1 text-body-md text-neutral-500">
            Immutable record of all admin actions in your organization.
          </p>
        </div>

        {/* Filters */}
        <div className="rounded-card border border-neutral-200 bg-white p-4 shadow-card">
          <div className="flex flex-wrap items-end gap-3">
            <div className="w-64">
              <Input
                placeholder="Search description…"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && applyFilters()}
              />
            </div>
            <div className="w-52">
              <Select
                options={ALL_ACTION_OPTIONS}
                value={action}
                onChange={(e) => setAction(e.target.value)}
              />
            </div>
            <div className="w-44">
              <Select
                options={ENTITY_TYPE_OPTIONS}
                value={entityType}
                onChange={(e) => setEntityType(e.target.value)}
              />
            </div>
            <DateRangePicker value={dateRange} onChange={setDateRange} />
            <button
              type="button"
              onClick={applyFilters}
              className="rounded-lg bg-primary-500 px-4 py-2 text-body-sm font-semibold text-white hover:bg-primary-600 transition-colors"
            >
              Apply
            </button>
          </div>
        </div>

        {isLoading ? (
          <div className="space-y-2">
            {Array.from({ length: 8 }).map((_, i) => (
              <div key={i} className="h-14 animate-pulse rounded-card bg-neutral-100" />
            ))}
          </div>
        ) : rows.length === 0 ? (
          <EmptyState
            title="No activity logged"
            description="Activity will appear here as admins perform actions."
          />
        ) : (
          <div className="space-y-0">
            <div className="overflow-hidden rounded-card border border-neutral-200 bg-white shadow-card">
              <DataTable<AuditLog> columns={columns} rows={rows} rowKey={(r) => r.id} />
            </div>

            {/* Expanded diff rows */}
            {rows.map((row) =>
              expandedId === row.id && row.changes ? (
                <div key={`diff-${row.id}`} className="border-x border-b border-neutral-200 bg-neutral-50 px-4 py-4">
                  <p className="mb-3 text-body-sm font-semibold text-night-800">
                    Changes for: <span className="font-normal text-neutral-600">{row.description}</span>
                  </p>
                  <AuditDiffViewer changes={row.changes} />
                </div>
              ) : null,
            )}
          </div>
        )}

        {meta && meta.last_page > 1 ? (
          <div className="flex items-center justify-between text-body-sm text-neutral-500">
            <span>{meta.total.toLocaleString()} entries</span>
            <div className="flex gap-2">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => setPage(page - 1)}
                className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
              >
                Previous
              </button>
              <span className="px-2 py-1.5">Page {page} of {meta.last_page}</span>
              <button
                type="button"
                disabled={page >= meta.last_page}
                onClick={() => setPage(page + 1)}
                className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
              >
                Next
              </button>
            </div>
          </div>
        ) : null}
      </div>
    </ProtectedRoute>
  );
}
