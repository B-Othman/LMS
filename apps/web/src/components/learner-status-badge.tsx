"use client";

import type { EnrollmentStatus } from "@securecy/types";

import { Badge } from "@securecy/ui";

const variantByStatus: Record<EnrollmentStatus, "info" | "success" | "neutral" | "warning"> = {
  active: "info",
  completed: "success",
  dropped: "neutral",
  expired: "warning",
};

const labelByStatus: Record<EnrollmentStatus, string> = {
  active: "In Progress",
  completed: "Completed",
  dropped: "Dropped",
  expired: "Expired",
};

export function LearnerStatusBadge({ status }: { status: EnrollmentStatus }) {
  return <Badge variant={variantByStatus[status]}>{labelByStatus[status]}</Badge>;
}
