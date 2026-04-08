import type { HTMLAttributes } from "react";

type AlertTone = "info" | "success" | "error";

interface AlertProps extends HTMLAttributes<HTMLDivElement> {
  title?: string;
  tone?: AlertTone;
}

const toneClasses: Record<AlertTone, string> = {
  info: "border-primary-200 bg-primary-50 text-primary-800",
  success: "border-success-200 bg-success-50 text-success-700",
  error: "border-error-200 bg-error-50 text-error-700",
};

export function Alert({
  title,
  tone = "info",
  className = "",
  children,
  ...props
}: AlertProps) {
  return (
    <div
      className={`rounded-card border px-4 py-3 ${toneClasses[tone]} ${className}`}
      role={tone === "error" ? "alert" : "status"}
      {...props}
    >
      {title ? <p className="text-body-md font-semibold">{title}</p> : null}
      {children ? <div className={title ? "mt-1 text-body-md" : "text-body-md"}>{children}</div> : null}
    </div>
  );
}
