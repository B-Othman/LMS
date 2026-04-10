interface HeatmapBarProps {
  value: number; // 0–100
  showLabel?: boolean;
  height?: number;
}

export function HeatmapBar({ value, showLabel = true, height = 8 }: HeatmapBarProps) {
  const color =
    value >= 80
      ? "bg-success-500"
      : value >= 50
        ? "bg-warning-400"
        : "bg-error-400";

  const textColor =
    value >= 80
      ? "text-success-700"
      : value >= 50
        ? "text-warning-700"
        : "text-error-700";

  return (
    <div className="flex items-center gap-2">
      <div className="flex-1 overflow-hidden rounded-full bg-neutral-100" style={{ height }}>
        <div
          className={`h-full rounded-full transition-all ${color}`}
          style={{ width: `${Math.min(100, Math.max(0, value))}%` }}
        />
      </div>
      {showLabel ? (
        <span className={`w-12 shrink-0 text-right text-body-sm font-semibold ${textColor}`}>
          {value.toFixed(1)}%
        </span>
      ) : null}
    </div>
  );
}
