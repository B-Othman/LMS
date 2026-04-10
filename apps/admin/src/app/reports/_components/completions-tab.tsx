"use client";

import type { CompletionRow } from "@securecy/types";
import { useCallback, useState } from "react";
import { Button, DataTable, type DataTableColumn, EmptyState, useToast } from "@securecy/ui";
import { DateRangePicker, type DateRange } from "@/components/date-range-picker";
import { ExportButton } from "@/components/export-button";
import { HeatmapBar } from "@/components/heatmap-bar";
import { fetchCompletionReport } from "@/lib/reports";
import { useReport } from "./use-report";

export function CompletionsTab() {
  const { showToast } = useToast();
  const [dateRange, setDateRange] = useState<DateRange>({ from: "", to: "" });
  const [applied, setApplied] = useState<DateRange>({ from: "", to: "" });

  const fetcher = useCallback(
    () =>
      fetchCompletionReport({
        date_from: applied.from || undefined,
        date_to: applied.to || undefined,
      }),
    [applied],
  );

  const { rows, meta, isLoading, page, setPage } = useReport(fetcher, [applied]);

  const columns: DataTableColumn<CompletionRow>[] = [
    {
      key: "title",
      header: "Course",
      render: (r) => <span className="font-medium text-night-900">{r.title}</span>,
    },
    {
      key: "category_name",
      header: "Category",
      render: (r) => <span className="text-body-sm text-neutral-500">{r.category_name ?? "—"}</span>,
    },
    {
      key: "total_enrolled",
      header: "Enrolled",
      render: (r) => <span>{r.total_enrolled.toLocaleString()}</span>,
    },
    {
      key: "completed_count",
      header: "Completed",
      render: (r) => <span>{r.completed_count.toLocaleString()}</span>,
    },
    {
      key: "completion_rate",
      header: "Rate",
      render: (r) => (
        <div className="min-w-32">
          <HeatmapBar value={r.completion_rate} />
        </div>
      ),
    },
    {
      key: "avg_days_to_complete",
      header: "Avg Days",
      render: (r) => (
        <span className="text-body-sm text-neutral-600">
          {r.avg_days_to_complete != null ? `${r.avg_days_to_complete}d` : "—"}
        </span>
      ),
    },
  ];

  return (
    <div className="space-y-4">
      {/* Filters */}
      <div className="flex flex-wrap items-end gap-3">
        <DateRangePicker value={dateRange} onChange={setDateRange} />
        <Button size="sm" onClick={() => setApplied(dateRange)}>
          Apply
        </Button>
        <div className="ml-auto flex gap-2">
          <ExportButton
            payload={{ report_type: "completions", format: "csv", filters: { date_from: applied.from || undefined, date_to: applied.to || undefined } }}
            label="Export CSV"
          />
          <ExportButton
            payload={{ report_type: "completions", format: "pdf", filters: { date_from: applied.from || undefined, date_to: applied.to || undefined } }}
            label="Export PDF"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="h-12 animate-pulse rounded-lg bg-neutral-100" />
          ))}
        </div>
      ) : rows.length === 0 ? (
        <EmptyState title="No completion data" description="Course completion data will appear here once learners start finishing courses." />
      ) : (
        <DataTable<CompletionRow> columns={columns} rows={rows} rowKey={(r) => r.id} />
      )}

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between text-body-sm text-neutral-500">
          <span>{meta.total.toLocaleString()} courses</span>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage(page - 1)}
              className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
            >
              Previous
            </button>
            <span className="px-2 py-1.5">
              Page {page} of {meta.last_page}
            </span>
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
