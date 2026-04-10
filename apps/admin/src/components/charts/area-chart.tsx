"use client";

import {
  Area,
  AreaChart as RechartsAreaChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";

interface AreaChartProps {
  data: Array<Record<string, unknown>>;
  xKey: string;
  yKey: string;
  height?: number;
}

export function AreaChart({ data, xKey, yKey, height = 240 }: AreaChartProps) {
  return (
    <ResponsiveContainer width="100%" height={height}>
      <RechartsAreaChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 0 }}>
        <defs>
          <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="#3b7ab8" stopOpacity={0.15} />
            <stop offset="95%" stopColor="#3b7ab8" stopOpacity={0} />
          </linearGradient>
        </defs>
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
        <Area
          type="monotone"
          dataKey={yKey}
          stroke="#3b7ab8"
          strokeWidth={2}
          fill="url(#areaGradient)"
          dot={{ fill: "#3b7ab8", r: 3, strokeWidth: 0 }}
        />
      </RechartsAreaChart>
    </ResponsiveContainer>
  );
}
