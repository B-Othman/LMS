"use client";

import { Button } from "./button";

interface PaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
  disabled?: boolean;
}

export function Pagination({
  currentPage,
  lastPage,
  onPageChange,
  disabled = false,
}: PaginationProps) {
  if (lastPage <= 1) {
    return null;
  }

  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(lastPage, startPage + 4);
  const pages = [];

  for (let page = startPage; page <= endPage; page += 1) {
    pages.push(page);
  }

  return (
    <div className="flex flex-wrap items-center justify-between gap-3">
      <p className="text-body-sm text-neutral-500">
        Page {currentPage} of {lastPage}
      </p>

      <div className="flex flex-wrap items-center gap-2">
        <Button
          type="button"
          variant="secondary"
          size="sm"
          disabled={disabled || currentPage <= 1}
          onClick={() => onPageChange(currentPage - 1)}
        >
          Previous
        </Button>

        {pages.map((page) => (
          <button
            key={page}
            type="button"
            disabled={disabled}
            onClick={() => onPageChange(page)}
            className={`inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-body-sm font-semibold transition-colors ${
              page === currentPage
                ? "border-primary-500 bg-primary-500 text-white"
                : "border-neutral-300 bg-white text-neutral-700 hover:border-primary-300 hover:bg-primary-50"
            } ${disabled ? "cursor-not-allowed opacity-50" : ""}`}
          >
            {page}
          </button>
        ))}

        <Button
          type="button"
          variant="secondary"
          size="sm"
          disabled={disabled || currentPage >= lastPage}
          onClick={() => onPageChange(currentPage + 1)}
        >
          Next
        </Button>
      </div>
    </div>
  );
}
