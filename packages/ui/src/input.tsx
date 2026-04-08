import { type InputHTMLAttributes, forwardRef } from "react";

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ error = false, className = "", ...props }, ref) => {
    return (
      <input
        ref={ref}
        className={`block w-full rounded-lg border px-3 py-2 text-body-md text-night-800 placeholder:text-neutral-400 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-50 ${
          error
            ? "border-error-500 focus:border-error-500 focus:ring-error-500"
            : "border-neutral-300 focus:border-primary-500 focus:ring-primary-500"
        } ${className}`}
        {...props}
      />
    );
  },
);

Input.displayName = "Input";
