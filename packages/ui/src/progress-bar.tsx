import type { HTMLAttributes } from "react";

interface ProgressBarProps extends HTMLAttributes<HTMLDivElement> {
  value: number;
  label?: string;
  showValue?: boolean;
}

export function ProgressBar({
  value,
  label,
  showValue = true,
  className = "",
  ...props
}: ProgressBarProps) {
  const normalizedValue = Math.max(0, Math.min(100, Math.round(value)));

  return (
    <div className={className} {...props}>
      {label || showValue ? (
        <div
          className={`mb-2 flex items-center gap-3 text-body-sm ${
            label ? "justify-between" : "justify-end"
          }`}
        >
          {label ? <span className="text-neutral-500">{label}</span> : null}
          {showValue ? <span className="font-semibold text-primary-700">{normalizedValue}%</span> : null}
        </div>
      ) : null}
      <div className="h-2.5 rounded-full bg-primary-100">
        <div
          className="h-2.5 rounded-full bg-primary-500 transition-[width]"
          style={{ width: `${normalizedValue}%` }}
        />
      </div>
    </div>
  );
}
