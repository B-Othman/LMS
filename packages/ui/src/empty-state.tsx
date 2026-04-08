import { type HTMLAttributes, type ReactNode, forwardRef } from "react";

interface EmptyStateProps extends HTMLAttributes<HTMLDivElement> {
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
}

export const EmptyState = forwardRef<HTMLDivElement, EmptyStateProps>(
  ({ icon, title, description, action, className = "", ...props }, ref) => {
    return (
      <div
        ref={ref}
        className={`flex flex-col items-center justify-center rounded-card border border-dashed border-neutral-300 bg-neutral-50 px-6 py-12 text-center ${className}`}
        {...props}
      >
        {icon && (
          <div className="mb-4 text-neutral-400">{icon}</div>
        )}
        <h3 className="text-h4 text-night-700">{title}</h3>
        {description && (
          <p className="mt-2 max-w-sm text-body-md text-neutral-500">
            {description}
          </p>
        )}
        {action && <div className="mt-6">{action}</div>}
      </div>
    );
  },
);

EmptyState.displayName = "EmptyState";
