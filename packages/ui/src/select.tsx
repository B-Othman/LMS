import { forwardRef, type SelectHTMLAttributes } from "react";

import { ChevronDownIcon } from "./icons";

export interface SelectOption {
  label: string;
  value: string;
}

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  error?: boolean;
  options?: SelectOption[];
  placeholder?: string;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ error = false, options = [], placeholder, className = "", children, ...props }, ref) => {
    return (
      <div className="relative">
        <select
          ref={ref}
          className={`block w-full appearance-none rounded-lg border bg-white px-3 py-2 pr-10 text-body-md text-night-800 transition-colors focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50 ${
            error
              ? "border-error-500 focus:border-error-500 focus:ring-error-500"
              : "border-neutral-300 focus:border-primary-500 focus:ring-primary-500"
          } ${className}`}
          {...props}
        >
          {placeholder ? <option value="">{placeholder}</option> : null}
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
          {children}
        </select>

        <ChevronDownIcon className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
      </div>
    );
  },
);

Select.displayName = "Select";
