"use client";

import type { LearnerProgressRow } from "@securecy/types";
import { useCallback, useState } from "react";
import { DataTable, type DataTableColumn, EmptyState, Input } from "@securecy/ui";
import { DateRangePicker, type DateRange } from "@/components/date-range-picker";
import { ExportButton } from "@/components/export-button";
import { Button } from "@securecy/ui";
import { fetchLearnerProgressReport } from "@/lib/reports";
import { useReport } from "./use-report";

export function LearnerProgressTab() {
  const [search, setSearch] = useState("");
  const [dateRange, setDateRange] = useState<DateRange>({ from: "", to: "" });
  const [appliedSearch, setAppliedSearch] = useState("");
  const [appliedDate, setAppliedDate] = useState<DateRange>({ from: "", to: "" });

  const fetcher = useCallback(
    () =>
      fetchLearnerProgressReport({
        search: appliedSearch || undefined,
        date_from: appliedDate.from || undefined,
        date_to: appliedDate.to || undefined,
      }),
    [appliedSearch, appliedDate],
  );

  const { rows, meta, isLoading, page, setPage } = useReport(fetcher, [appliedSearch, appliedDate]);

  const columns: DataTableColumn<LearnerProgressRow>[] = [
    {
      key: "name",
      header: "Learner",
      render: (r) => (
        <div>
          <p className="font-medium text-night-900">{r.name}</p>
          <p className="text-body-sm text-neutral-500">{r.email}</p>
        </div>
      ),
    },
    {
      key: "enrolled_count",
      header: "Enrolled",
      render: (r) => <span>{r.enrolled_count}</span>,
    },
    {
      key: "completed_count",
      header: "Completed",
      render: (r) => (
        <span className="font-medium text-success-700">{r.completed_count}</span>
      ),
    },
    {
      key: "in_progress_count",
      header: "In Progress",
      render: (r) => <span className="text-warning-700">{r.in_progress_count}</span>,
    },
    {
      key: "avg_score",
      header: "Avg Score",
      render: (r) => (
        <span className="text-body-sm">
          {r.avg_score != null ? `${r.avg_score}%` : "—"}
        </span>
      ),
    },
  ];

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-3">
        <Input
          placeholder="Search learner name or email…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-64"
        />
        <DateRangePicker value={dateRange} onChange={setDateRange} />
        <Button
          size="sm"
          onClick={() => {
            setAppliedSearch(search);
            setAppliedDate(dateRange);
          }}
        >
          Apply
        </Button>
        <div className="ml-auto flex gap-2">
          <ExportButton
            payload={{ report_type: "learner_progress", format: "csv", filters: { search: appliedSearch || undefined, date_from: appliedDate.from || undefined, date_to: appliedDate.to || undefined } }}
            label="Export CSV"
          />
          <ExportButton
            payload={{ report_type: "learner_progress", format: "pdf", filters: { search: appliedSearch || undefined, date_from: appliedDate.from || undefined, date_to: appliedDate.to || undefined } }}
            label="Export PDF"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="h-14 animate-pulse rounded-lg bg-neutral-100" />
          ))}
        </div>
      ) : rows.length === 0 ? (
        <EmptyState title="No learner data" description="Learner progress will appear here once enrollments exist." />
      ) : (
        <DataTable<LearnerProgressRow> columns={columns} rows={rows} rowKey={(r) => r.id} />
      )}

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between text-body-sm text-neutral-500">
          <span>{meta.total.toLocaleString()} learners</span>
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
  );
}
