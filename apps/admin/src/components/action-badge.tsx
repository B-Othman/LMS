import { Badge } from "@securecy/ui";

const ACTION_CONFIG: Record<string, { label: string; variant: "success" | "error" | "warning" | "info" | "neutral" }> = {
  "user.created": { label: "User Created", variant: "success" },
  "user.updated": { label: "User Updated", variant: "info" },
  "user.deleted": { label: "User Deleted", variant: "error" },
  "user.suspended": { label: "User Suspended", variant: "warning" },
  "user.login": { label: "Login", variant: "neutral" },
  "user.logout": { label: "Logout", variant: "neutral" },
  "user.role_assigned": { label: "Role Assigned", variant: "info" },
  "user.role_removed": { label: "Role Removed", variant: "warning" },
  "course.created": { label: "Course Created", variant: "success" },
  "course.updated": { label: "Course Updated", variant: "info" },
  "course.published": { label: "Course Published", variant: "success" },
  "course.archived": { label: "Course Archived", variant: "warning" },
  "course.deleted": { label: "Course Deleted", variant: "error" },
  "enrollment.created": { label: "Enrollment Created", variant: "success" },
  "enrollment.dropped": { label: "Enrollment Dropped", variant: "warning" },
  "certificate.issued": { label: "Certificate Issued", variant: "success" },
  "certificate.revoked": { label: "Certificate Revoked", variant: "error" },
  "quiz.attempt_submitted": { label: "Quiz Submitted", variant: "info" },
  "export.requested": { label: "Export Requested", variant: "neutral" },
  "settings.updated": { label: "Settings Updated", variant: "info" },
};

export function ActionBadge({ action }: { action: string }) {
  const config = ACTION_CONFIG[action];

  if (!config) {
    return (
      <Badge variant="neutral" className="font-mono text-[11px]">
        {action}
      </Badge>
    );
  }

  return <Badge variant={config.variant}>{config.label}</Badge>;
}

export const ACTION_OPTIONS = Object.entries(ACTION_CONFIG).map(([value, { label }]) => ({
  value,
  label,
}));
