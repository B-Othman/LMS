"use client";

import {
  Bar,
  BarChart as RechartsBarChart,
  CartesianGrid,
  Cell,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";

interface BarChartProps {
  data: Array<Record<string, unknown>>;
  xKey: string;
  yKey: string;
  layout?: "horizontal" | "vertical";
  height?: number;
  maxBarSize?: number;
}

export function BarChart({ data, xKey, yKey, layout = "vertical", height = 220, maxBarSize = 28 }: BarChartProps) {
  if (layout === "horizontal") {
    return (
      <ResponsiveContainer width="100%" height={height}>
        <RechartsBarChart
          data={data}
          layout="vertical"
          margin={{ top: 4, right: 24, left: 8, bottom: 0 }}
        >
          <CartesianGrid strokeDasharray="3 3" stroke="#e8edf4" horizontal={false} />
          <XAxis type="number" tick={{ fontSize: 11, fill: "#6b7a99" }} tickLine={false} axisLine={false} />
          <YAxis
            type="category"
            dataKey={xKey}
            tick={{ fontSize: 11, fill: "#6b7a99" }}
            tickLine={false}
            axisLine={false}
            width={140}
            tickFormatter={(v: string) => v.length > 22 ? v.slice(0, 20) + "…" : v}
          />
          <Tooltip
            contentStyle={{
              background: "white",
              border: "1px solid #e8edf4",
              borderRadius: 8,
              fontSize: 12,
            }}
          />
          <Bar dataKey={yKey} fill="#3b7ab8" radius={[0, 4, 4, 0]} maxBarSize={maxBarSize}>
            {data.map((_, i) => (
              <Cell key={i} fill={i % 2 === 0 ? "#3b7ab8" : "#5b9fd4"} />
            ))}
          </Bar>
        </RechartsBarChart>
      </ResponsiveContainer>
    );
  }

  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsBarChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#e8edf4" vertical={false} />
        <XAxis
          dataKey={xKey}
          tick={{ fontSize: 11, fill: "#6b7a99" }}
          tickLine={false}
          axisLine={false}
          tickFormatter={(v: string) => v.length > 10 ? v.slice(0, 8) + "…" : v}
        />
        <YAxis
          tick={{ fontSize: 11, fill: "#6b7a99" }}
          tickLine={false}
          axisLine={false}
          width={36}
        />
        <Tooltip
          contentStyle={{
            background: "white",
            border: "1px solid #e8edf4",
            borderRadius: 8,
            fontSize: 12,
          }}
        />
        <Bar dataKey={yKey} fill="#3b7ab8" radius={[4, 4, 0, 0]} maxBarSize={maxBarSize} />
      </RechartsBarChart>
    </ResponsiveContainer>
  );
}
