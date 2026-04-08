import { type LabelHTMLAttributes, forwardRef } from "react";

interface LabelProps extends LabelHTMLAttributes<HTMLLabelElement> {
  required?: boolean;
}

export const Label = forwardRef<HTMLLabelElement, LabelProps>(
  ({ required = false, className = "", children, ...props }, ref) => {
    return (
      <label
        ref={ref}
        className={`block text-body-md font-medium text-night-700 ${className}`}
        {...props}
      >
        {children}
        {required && <span className="ml-0.5 text-error-500">*</span>}
      </label>
    );
  },
);

Label.displayName = "Label";
