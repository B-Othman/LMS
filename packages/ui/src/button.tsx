import { type ButtonHTMLAttributes, forwardRef } from "react";

type ButtonVariant = "primary" | "secondary" | "danger" | "success";
type ButtonSize = "sm" | "md" | "lg";

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
}

const variantClasses: Record<ButtonVariant, string> = {
  primary:
    "bg-primary-500 text-white hover:bg-primary-300 active:bg-primary-700 focus-visible:ring-primary-500",
  secondary:
    "bg-neutral-100 border border-neutral-300 text-neutral-700 hover:bg-neutral-200 active:bg-neutral-400 focus-visible:ring-neutral-500",
  danger:
    "bg-error-500 text-white hover:bg-error-400 active:bg-error-700 focus-visible:ring-error-500",
  success:
    "bg-success-500 text-white hover:bg-success-400 active:bg-success-700 focus-visible:ring-success-500",
};

const sizeClasses: Record<ButtonSize, string> = {
  sm: "px-3 py-1.5 text-body-sm",
  md: "px-4 py-2 text-button",
  lg: "px-6 py-3 text-button",
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ variant = "primary", size = "md", className = "", children, ...props }, ref) => {
    return (
      <button
        ref={ref}
        className={`inline-flex items-center justify-center rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 ${variantClasses[variant]} ${sizeClasses[size]} ${className}`}
        {...props}
      >
        {children}
      </button>
    );
  },
);

Button.displayName = "Button";
