"use client";

import {
  CartesianGrid,
  Line,
  LineChart as RechartsLineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";

interface LineChartProps {
  data: Array<Record<string, unknown>>;
  xKey: string;
  yKey: string;
  yLabel?: string;
  height?: number;
}

export function LineChart({ data, xKey, yKey, yLabel, height = 260 }: LineChartProps) {
  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsLineChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#e8edf4" vertical={false} />
        <XAxis
          dataKey={xKey}
          tick={{ fontSize: 11, fill: "#6b7a99" }}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          tick={{ fontSize: 11, fill: "#6b7a99" }}
          tickLine={false}
          axisLine={false}
          label={yLabel ? { value: yLabel, angle: -90, position: "insideLeft", fontSize: 11, fill: "#6b7a99" } : undefined}
          width={36}
        />
        <Tooltip
          contentStyle={{
            background: "white",
            border: "1px solid #e8edf4",
            borderRadius: 8,
            fontSize: 12,
            boxShadow: "0 2px 8px rgba(0,0,0,0.08)",
          }}
          cursor={{ stroke: "#3b7ab8", strokeWidth: 1 }}
        />
        <Line
          type="monotone"
          dataKey={yKey}
          stroke="#3b7ab8"
          strokeWidth={2}
          dot={{ fill: "#3b7ab8", r: 3, strokeWidth: 0 }}
          activeDot={{ r: 5, fill: "#3b7ab8" }}
        />
      </RechartsLineChart>
    </ResponsiveContainer>
  );
}
