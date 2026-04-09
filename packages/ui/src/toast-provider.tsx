"use client";

import {
  createContext,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from "react";

import { CloseIcon } from "./icons";

type ToastTone = "success" | "error" | "info";

interface ToastInput {
  title?: string;
  message: string;
  tone?: ToastTone;
}

interface Toast extends ToastInput {
  id: number;
}

interface ToastContextValue {
  showToast: (input: ToastInput) => void;
}

const toneClasses: Record<ToastTone, string> = {
  success: "border-success-200 bg-success-50 text-success-700",
  error: "border-error-200 bg-error-50 text-error-700",
  info: "border-primary-200 bg-primary-50 text-primary-700",
};

const ToastContext = createContext<ToastContextValue | null>(null);

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const value = useMemo<ToastContextValue>(
    () => ({
      showToast: ({ tone = "info", ...input }) => {
        const id = Date.now() + Math.floor(Math.random() * 1000);
        const toast = { id, tone, ...input };

        setToasts((current) => [...current, toast]);

        window.setTimeout(() => {
          setToasts((current) => current.filter((item) => item.id !== id));
        }, 4000);
      },
    }),
    [],
  );

  return (
    <ToastContext.Provider value={value}>
      {children}

      <div className="pointer-events-none fixed right-4 top-4 z-[60] flex w-full max-w-sm flex-col gap-3">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className={`pointer-events-auto rounded-card border px-4 py-3 shadow-card ${toneClasses[toast.tone ?? "info"]}`}
          >
            <div className="flex items-start justify-between gap-3">
              <div>
                {toast.title ? <p className="text-body-md font-semibold">{toast.title}</p> : null}
                <p className="text-body-sm">{toast.message}</p>
              </div>

              <button
                type="button"
                onClick={() => setToasts((current) => current.filter((item) => item.id !== toast.id))}
                className="rounded-md p-1 transition-colors hover:bg-white/70"
                aria-label="Dismiss notification"
              >
                <CloseIcon className="h-4 w-4" />
              </button>
            </div>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast(): ToastContextValue {
  const context = useContext(ToastContext);

  if (!context) {
    throw new Error("useToast must be used within a ToastProvider.");
  }

  return context;
}
