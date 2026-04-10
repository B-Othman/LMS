"use client";

import type { CourseDetailReport } from "@securecy/types";
import Link from "next/link";
import { use, useEffect, useState } from "react";
import { EmptyState, ProtectedRoute } from "@securecy/ui";
import { AreaChart } from "@/components/charts/area-chart";
import { ExportButton } from "@/components/export-button";
import { HeatmapBar } from "@/components/heatmap-bar";
import { KPICard } from "@/components/kpi-card";
import { fetchCourseDetailReport } from "@/lib/reports";

function formatMonth(ym: string): string {
  const [year, month] = ym.split("-");
  const date = new Date(Number(year), Number(month) - 1, 1);

  return date.toLocaleDateString(undefined, { month: "short", year: "2-digit" });
}

export default function CourseDetailReportPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params);
  const courseId = Number(id);

  const [report, setReport] = useState<CourseDetailReport | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchCourseDetailReport(courseId)
      .then(setReport)
      .catch(() => setError("Failed to load course report."))
      .finally(() => setIsLoading(false));
  }, [courseId]);

  if (isLoading) {
    return (
      <ProtectedRoute requiredPermissions={["reports.view"]}>
        <div className="mx-auto max-w-5xl space-y-6">
          <div className="h-8 w-64 animate-pulse rounded bg-neutral-100" />
          <div className="grid grid-cols-2 gap-5 md:grid-cols-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="h-24 animate-pulse rounded-card bg-neutral-100" />
            ))}
          </div>
          <div className="h-60 animate-pulse rounded-card bg-neutral-100" />
        </div>
      </ProtectedRoute>
    );
  }

  if (error || !report) {
    return (
      <ProtectedRoute requiredPermissions={["reports.view"]}>
        <EmptyState title="Report unavailable" description={error ?? "No data found."} />
      </ProtectedRoute>
    );
  }

  const { overview, enrollment_timeline, lesson_completion, dropoff } = report;

  const timelineData = enrollment_timeline.map((d) => ({
    month: formatMonth(d.month),
    cumulative: d.cumulative,
    monthly: d.count,
  }));

  return (
    <ProtectedRoute requiredPermissions={["reports.view"]}>
      <div className="mx-auto max-w-5xl space-y-6">
        {/* Header */}
        <div className="flex items-start justify-between gap-4">
          <div>
            <Link href="/reports" className="text-body-sm text-primary-600 hover:underline">
              ← Back to Reports
            </Link>
            <h1 className="mt-2 text-h2 font-bold text-night-900">
              {overview?.title ?? "Course Detail"}
            </h1>
            <p className="mt-1 text-body-md text-neutral-500">Course detail report</p>
          </div>
          <ExportButton
            payload={{ report_type: "course_detail", format: "csv", filters: { course_id: courseId } }}
            label="Export CSV"
          />
        </div>

        {/* KPIs */}
        {overview ? (
          <div className="grid grid-cols-2 gap-5 md:grid-cols-4">
            <KPICard
              label="Total Enrolled"
              value={overview.total_enrolled.toLocaleString()}
              icon={<span className="text-lg font-bold">👥</span>}
              accentClassName="bg-primary-50 text-primary-700"
            />
            <KPICard
              label="Completed"
              value={overview.completed_count.toLocaleString()}
              icon={<span className="text-lg">✓</span>}
              accentClassName="bg-success-50 text-success-700"
            />
            <KPICard
              label="Completion Rate"
              value={`${overview.completion_rate}%`}
              icon={<span className="text-lg">📊</span>}
              accentClassName="bg-warning-50 text-warning-700"
            />
            <KPICard
              label="Avg Days to Complete"
              value={overview.avg_days_to_complete != null ? `${overview.avg_days_to_complete}d` : "—"}
              icon={<span className="text-lg">⏱</span>}
              accentClassName="bg-neutral-100 text-neutral-700"
            />
          </div>
        ) : null}

        {/* Enrollment timeline */}
        <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <h2 className="mb-4 text-body-md font-semibold text-night-900">Enrollment Timeline</h2>
          {timelineData.length > 0 ? (
            <AreaChart data={timelineData} xKey="month" yKey="cumulative" height={200} />
          ) : (
            <div className="flex h-32 items-center justify-center text-body-sm text-neutral-400">
              No enrollment history
            </div>
          )}
        </div>

        {/* Lesson completion heatmap */}
        <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <h2 className="mb-4 text-body-md font-semibold text-night-900">Lesson Completion</h2>
          {lesson_completion.length > 0 ? (
            <div className="divide-y divide-neutral-100">
              {lesson_completion.map((lesson) => (
                <div key={lesson.lesson_id} className="grid grid-cols-[1fr_200px_80px] items-center gap-4 py-3">
                  <div className="min-w-0">
                    <p className="truncate text-body-sm font-medium text-night-900">{lesson.title}</p>
                    <p className="text-body-sm text-neutral-500">{lesson.module_title}</p>
                  </div>
                  <HeatmapBar value={lesson.completion_rate} />
                  <span className="text-right text-body-sm text-neutral-500">
                    {lesson.completed_count}/{lesson.total_enrolled}
                  </span>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-body-sm text-neutral-400">No lesson progress data yet.</div>
          )}
        </div>

        {/* Drop-off funnel */}
        <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <h2 className="mb-1 text-body-md font-semibold text-night-900">Drop-off Analysis</h2>
          <p className="mb-4 text-body-sm text-neutral-500">
            How many learners reached each lesson (started or completed it).
          </p>
          {dropoff.length > 0 ? (
            <div className="space-y-2">
              {dropoff.map((lesson, i) => {
                const prevReach = i > 0 ? dropoff[i - 1].reach_rate : 100;
                const dropped = prevReach - lesson.reach_rate;

                return (
                  <div key={lesson.lesson_id} className="flex items-center gap-3">
                    <span className="w-6 shrink-0 text-right text-body-sm text-neutral-400">{i + 1}</span>
                    <div className="flex-1">
                      <div className="flex items-center justify-between gap-2">
                        <span className="truncate text-body-sm text-night-800">{lesson.title}</span>
                        <span className="shrink-0 text-body-sm font-semibold text-night-900">
                          {lesson.reach_rate.toFixed(1)}%
                        </span>
                      </div>
                      <div className="mt-1 flex items-center gap-2">
                        <div className="flex-1 overflow-hidden rounded-full bg-neutral-100" style={{ height: 6 }}>
                          <div
                            className="h-full rounded-full bg-primary-400 transition-all"
                            style={{ width: `${lesson.reach_rate}%` }}
                          />
                        </div>
                        {i > 0 && dropped > 2 ? (
                          <span className="shrink-0 text-body-sm text-error-600">-{dropped.toFixed(1)}%</span>
                        ) : null}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="text-body-sm text-neutral-400">No progress data yet.</div>
          )}
        </div>
      </div>
    </ProtectedRoute>
  );
}
