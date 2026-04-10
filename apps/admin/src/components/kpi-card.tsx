import type { ReactNode } from "react";

interface KPICardProps {
  label: string;
  value: string | number;
  icon: ReactNode;
  accentClassName?: string;
  trend?: {
    value: number;
    label: string;
  };
}

export function KPICard({
  label,
  value,
  icon,
  accentClassName = "bg-primary-50 text-primary-700",
  trend,
}: KPICardProps) {
  return (
    <div className="rounded-card border border-neutral-200 bg-white p-5 shadow-card">
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <p className="text-body-sm font-medium uppercase tracking-[0.08em] text-neutral-500">{label}</p>
          <p className="mt-3 text-metric text-night-900">{value}</p>
          {trend ? (
            <p
              className={`mt-2 flex items-center gap-1 text-body-sm font-medium ${
                trend.value >= 0 ? "text-success-600" : "text-error-600"
              }`}
            >
              <span>{trend.value >= 0 ? "↑" : "↓"}</span>
              <span>{trend.label}</span>
            </p>
          ) : null}
        </div>
        <div
          className={`inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ${accentClassName}`}
        >
          {icon}
        </div>
      </div>
    </div>
  );
}
