"use client";

import { useEffect, type ReactNode } from "react";

import { CloseIcon } from "./icons";

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  description?: string;
  children: ReactNode;
  footer?: ReactNode;
  size?: "md" | "lg" | "xl";
}

export function Modal({
  open,
  onClose,
  title,
  description,
  children,
  footer,
  size = "md",
}: ModalProps) {
  useEffect(() => {
    if (!open) {
      return;
    }

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") {
        onClose();
      }
    }

    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open, onClose]);

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-night-950/45 px-4 py-6">
      <div
        className="absolute inset-0"
        onClick={onClose}
        aria-hidden="true"
      />

      <div className={`relative z-10 w-full rounded-card border border-neutral-200 bg-white p-6 shadow-card ${sizeClasses[size]}`}>
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 className="text-h4 text-night-900">{title}</h2>
            {description ? <p className="mt-2 text-body-md text-neutral-500">{description}</p> : null}
          </div>

          <button
            type="button"
            onClick={onClose}
            className="inline-flex rounded-lg p-2 text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-night-900"
            aria-label="Close modal"
          >
            <CloseIcon className="h-4 w-4" />
          </button>
        </div>

        <div className="mt-5">{children}</div>
        {footer ? <div className="mt-6 flex flex-wrap justify-end gap-3">{footer}</div> : null}
      </div>
    </div>
  );
}

const sizeClasses = {
  md: "max-w-lg",
  lg: "max-w-3xl",
  xl: "max-w-5xl",
};
