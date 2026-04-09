import type { HTMLAttributes, ReactNode } from "react";

interface StatsCardProps extends HTMLAttributes<HTMLDivElement> {
  icon: ReactNode;
  value: string | number;
  label: string;
  accentClassName?: string;
}

export function StatsCard({
  icon,
  value,
  label,
  accentClassName = "bg-primary-50 text-primary-700",
  className = "",
  ...props
}: StatsCardProps) {
  return (
    <div
      className={`rounded-card border border-neutral-200 bg-white p-5 shadow-card ${className}`}
      {...props}
    >
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-body-sm font-medium uppercase tracking-[0.08em] text-neutral-500">{label}</p>
          <p className="mt-3 text-metric text-night-900">{value}</p>
        </div>
        <div className={`inline-flex h-12 w-12 items-center justify-center rounded-2xl ${accentClassName}`}>
          {icon}
        </div>
      </div>
    </div>
  );
}
