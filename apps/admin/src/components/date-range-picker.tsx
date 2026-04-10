"use client";

import { useState } from "react";

export interface DateRange {
  from: string;
  to: string;
}

interface DateRangePickerProps {
  value: DateRange;
  onChange: (range: DateRange) => void;
}

const PRESETS: Array<{ label: string; getValue: () => DateRange }> = [
  {
    label: "Last 7 days",
    getValue: () => ({
      from: new Date(Date.now() - 6 * 86400000).toISOString().slice(0, 10),
      to: new Date().toISOString().slice(0, 10),
    }),
  },
  {
    label: "Last 30 days",
    getValue: () => ({
      from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
      to: new Date().toISOString().slice(0, 10),
    }),
  },
  {
    label: "This quarter",
    getValue: () => {
      const now = new Date();
      const q = Math.floor(now.getMonth() / 3);
      const qStart = new Date(now.getFullYear(), q * 3, 1);

      return {
        from: qStart.toISOString().slice(0, 10),
        to: now.toISOString().slice(0, 10),
      };
    },
  },
  {
    label: "This year",
    getValue: () => ({
      from: new Date(new Date().getFullYear(), 0, 1).toISOString().slice(0, 10),
      to: new Date().toISOString().slice(0, 10),
    }),
  },
];

export function DateRangePicker({ value, onChange }: DateRangePickerProps) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5">
        <label className="text-body-sm text-neutral-500">From</label>
        <input
          type="date"
          value={value.from}
          onChange={(e) => onChange({ ...value, from: e.target.value })}
          className="text-body-sm text-night-900 focus:outline-none"
        />
      </div>
      <div className="flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5">
        <label className="text-body-sm text-neutral-500">To</label>
        <input
          type="date"
          value={value.to}
          onChange={(e) => onChange({ ...value, to: e.target.value })}
          className="text-body-sm text-night-900 focus:outline-none"
        />
      </div>
      <div className="flex gap-1">
        {PRESETS.map((preset) => (
          <button
            key={preset.label}
            type="button"
            onClick={() => onChange(preset.getValue())}
            className="rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-body-sm text-neutral-600 hover:bg-primary-50 hover:border-primary-300 hover:text-primary-700 transition-colors"
          >
            {preset.label}
          </button>
        ))}
      </div>
    </div>
  );
}
