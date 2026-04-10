"use client";

import type { OverviewStats } from "@securecy/types";
import { useEffect, useState } from "react";
import { AwardIcon, CertificatesIcon, CoursesIcon, EnrollmentsIcon, UsersIcon } from "@securecy/ui";
import { AreaChart } from "@/components/charts/area-chart";
import { BarChart } from "@/components/charts/bar-chart";
import { KPICard } from "@/components/kpi-card";
import { fetchOverviewStats } from "@/lib/reports";

function formatMonth(ym: string): string {
  const [year, month] = ym.split("-");
  const date = new Date(Number(year), Number(month) - 1, 1);

  return date.toLocaleDateString(undefined, { month: "short", year: "2-digit" });
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
  });
}

export function AdminDashboardPage() {
  const [stats, setStats] = useState<OverviewStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchOverviewStats()
      .then(setStats)
      .catch(() => setError("Failed to load dashboard data."))
      .finally(() => setIsLoading(false));
  }, []);

  if (isLoading) {
    return (
      <div className="mx-auto max-w-7xl space-y-6">
        <div>
          <div className="h-8 w-48 animate-pulse rounded bg-neutral-100" />
          <div className="mt-2 h-4 w-72 animate-pulse rounded bg-neutral-100" />
        </div>
        <div className="grid grid-cols-2 gap-5 md:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-28 animate-pulse rounded-card bg-neutral-100" />
          ))}
        </div>
        <div className="h-72 animate-pulse rounded-card bg-neutral-100" />
      </div>
    );
  }

  if (error || !stats) {
    return (
      <div className="mx-auto max-w-7xl">
        <p className="text-body-md text-error-600">{error ?? "No data available."}</p>
      </div>
    );
  }

  const chartData = stats.completions_by_month.map((d) => ({
    month: formatMonth(d.month),
    completions: d.count,
  }));

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <div>
        <h1 className="text-h2 font-bold text-night-900">Dashboard</h1>
        <p className="mt-1 text-body-md text-neutral-500">Organization overview and key metrics.</p>
      </div>

      {/* KPI row */}
      <div className="grid grid-cols-2 gap-5 md:grid-cols-4">
        <KPICard
          label="Total Users"
          value={stats.total_users.toLocaleString()}
          icon={<UsersIcon className="h-6 w-6" />}
          accentClassName="bg-primary-50 text-primary-700"
        />
        <KPICard
          label="Active Courses"
          value={stats.total_courses.toLocaleString()}
          icon={<CoursesIcon className="h-6 w-6" />}
          accentClassName="bg-success-50 text-success-700"
        />
        <KPICard
          label="Enrollments This Month"
          value={stats.enrollments_this_month.toLocaleString()}
          icon={<EnrollmentsIcon className="h-6 w-6" />}
          accentClassName="bg-warning-50 text-warning-700"
          trend={{
            value: stats.enrollments_this_month,
            label: `${stats.total_enrollments.toLocaleString()} total`,
          }}
        />
        <KPICard
          label="Completion Rate"
          value={`${stats.avg_completion_rate}%`}
          icon={<AwardIcon className="h-6 w-6" />}
          accentClassName="bg-primary-50 text-primary-700"
          trend={{
            value: stats.completions_this_month,
            label: `${stats.completions_this_month} this month`,
          }}
        />
      </div>

      {/* Completion trend */}
      <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <h2 className="text-body-md font-semibold text-night-900">Completions Over Time</h2>
            <p className="text-body-sm text-neutral-500">Monthly completions over the last 12 months</p>
          </div>
          <span className="rounded-full bg-success-50 px-3 py-1 text-body-sm font-semibold text-success-700">
            {stats.total_completions.toLocaleString()} total
          </span>
        </div>
        {chartData.length > 0 ? (
          <AreaChart data={chartData} xKey="month" yKey="completions" height={240} />
        ) : (
          <div className="flex h-40 items-center justify-center text-body-sm text-neutral-400">
            No completion data yet
          </div>
        )}
      </div>

      {/* Two-column row */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {/* Top courses */}
        <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <h2 className="mb-4 text-body-md font-semibold text-night-900">Top 5 Courses by Enrollment</h2>
          {stats.top_courses.length > 0 ? (
            <BarChart
              data={stats.top_courses.map((c) => ({
                title: c.title,
                enrollments: c.enrollment_count,
              }))}
              xKey="title"
              yKey="enrollments"
              layout="horizontal"
              height={220}
            />
          ) : (
            <div className="flex h-40 items-center justify-center text-body-sm text-neutral-400">
              No enrollments yet
            </div>
          )}
        </div>

        {/* Recent enrollments */}
        <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <h2 className="mb-4 text-body-md font-semibold text-night-900">Recent Enrollments</h2>
          {stats.recent_enrollments.length > 0 ? (
            <div className="divide-y divide-neutral-100">
              {stats.recent_enrollments.slice(0, 8).map((e) => (
                <div key={e.id} className="flex items-center justify-between gap-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-body-sm font-medium text-night-900">{e.learner_name}</p>
                    <p className="truncate text-body-sm text-neutral-500">{e.course_title}</p>
                  </div>
                  <span className="shrink-0 text-body-sm text-neutral-400">{formatDate(e.enrolled_at)}</span>
                </div>
              ))}
            </div>
          ) : (
            <div className="flex h-40 items-center justify-center text-body-sm text-neutral-400">
              No recent enrollments
            </div>
          )}
        </div>
      </div>

      {/* Bottom certificates stat */}
      <div className="rounded-card border border-neutral-200 bg-white p-4 shadow-card">
        <div className="flex items-center gap-4">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-warning-50 text-warning-600">
            <CertificatesIcon className="h-5 w-5" />
          </div>
          <div>
            <p className="text-body-sm text-neutral-500">Total Certificates Issued</p>
            <p className="text-h3 font-bold text-night-900">{stats.total_certificates.toLocaleString()}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
