"use client";

import { useEffect, useRef, useState, type ReactNode } from "react";

import { ChevronDownIcon, CloseIcon } from "./icons";

export interface MultiSelectOption {
  label: string;
  value: string;
  description?: string;
}

interface MultiSelectProps {
  value: string[];
  options: MultiSelectOption[];
  onChange: (value: string[]) => void;
  placeholder?: string;
  error?: boolean;
  disabled?: boolean;
  emptyState?: ReactNode;
}

export function MultiSelect({
  value,
  options,
  onChange,
  placeholder = "Select options",
  error = false,
  disabled = false,
  emptyState,
}: MultiSelectProps) {
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    function handlePointerDown(event: MouseEvent) {
      if (!containerRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    }

    document.addEventListener("mousedown", handlePointerDown);

    return () => {
      document.removeEventListener("mousedown", handlePointerDown);
    };
  }, [open]);

  const selectedOptions = options.filter((option) => value.includes(option.value));

  function toggleOption(optionValue: string) {
    if (value.includes(optionValue)) {
      onChange(value.filter((current) => current !== optionValue));
      return;
    }

    onChange([...value, optionValue]);
  }

  return (
    <div className="relative" ref={containerRef}>
      <button
        type="button"
        onClick={() => {
          if (!disabled) {
            setOpen((current) => !current);
          }
        }}
        className={`flex min-h-[42px] w-full items-center justify-between gap-3 rounded-lg border bg-white px-3 py-2 text-left transition-colors ${
          error
            ? "border-error-500 focus-visible:ring-error-500"
            : "border-neutral-300 focus-visible:ring-primary-500"
        } ${
          disabled ? "cursor-not-allowed opacity-50" : "hover:border-primary-300"
        } focus-visible:outline-none focus-visible:ring-2`}
        disabled={disabled}
      >
        <div className="flex flex-1 flex-wrap gap-2">
          {selectedOptions.length > 0 ? (
            selectedOptions.map((option) => (
              <span
                key={option.value}
                className="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-body-sm font-medium text-primary-700"
              >
                {option.label}
                <span
                  onClick={(event) => {
                    event.stopPropagation();
                    toggleOption(option.value);
                  }}
                  className="inline-flex h-4 w-4 items-center justify-center rounded-full hover:bg-primary-100"
                  aria-hidden="true"
                >
                  <CloseIcon className="h-3 w-3" />
                </span>
              </span>
            ))
          ) : (
            <span className="text-body-md text-neutral-400">{placeholder}</span>
          )}
        </div>

        <ChevronDownIcon className={`h-4 w-4 shrink-0 text-neutral-500 transition-transform ${open ? "rotate-180" : ""}`} />
      </button>

      {open ? (
        <div className="absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-card border border-neutral-200 bg-white p-2 shadow-card">
          {options.length > 0 ? (
            options.map((option) => {
              const checked = value.includes(option.value);

              return (
                <label
                  key={option.value}
                  className={`flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-primary-50 ${
                    checked ? "bg-primary-50" : ""
                  }`}
                >
                  <input
                    type="checkbox"
                    checked={checked}
                    onChange={() => toggleOption(option.value)}
                    className="mt-0.5 h-4 w-4 rounded border-neutral-300 text-primary-500 focus:ring-primary-500"
                  />
                  <span className="min-w-0">
                    <span className="block text-body-md font-medium text-night-900">{option.label}</span>
                    {option.description ? (
                      <span className="block text-body-sm text-neutral-500">{option.description}</span>
                    ) : null}
                  </span>
                </label>
              );
            })
          ) : (
            <div className="px-3 py-4 text-body-sm text-neutral-500">
              {emptyState ?? "No options available."}
            </div>
          )}
        </div>
      ) : null}
    </div>
  );
}
