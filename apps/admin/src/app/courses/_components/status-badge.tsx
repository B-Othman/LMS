"use client";

import type { CourseStatus } from "@securecy/types";

import { Badge } from "@securecy/ui";

const statusConfig: Record<CourseStatus, { variant: "success" | "warning" | "neutral"; label: string }> = {
  draft: { variant: "warning", label: "Draft" },
  published: { variant: "success", label: "Published" },
  archived: { variant: "neutral", label: "Archived" },
};

export function StatusBadge({ status }: { status: CourseStatus }) {
  const config = statusConfig[status];

  return <Badge variant={config.variant}>{config.label}</Badge>;
}
