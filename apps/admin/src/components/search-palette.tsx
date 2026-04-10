"use client";

import type { SearchResults } from "@securecy/types";
import { useEffect, useRef, useState } from "react";
import { Avatar, Badge } from "@securecy/ui";
import { search } from "@/lib/search";

interface SearchPaletteProps {
  onNavigate: (path: string) => void;
}

function useDebounce<T>(value: T, delay: number): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const id = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(id);
  }, [value, delay]);

  return debounced;
}

const STATUS_VARIANTS: Record<string, "success" | "warning" | "neutral" | "error"> = {
  active: "success",
  published: "success",
  inactive: "neutral",
  draft: "neutral",
  archived: "neutral",
  suspended: "error",
};

export function SearchPalette({ onNavigate }: SearchPaletteProps) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchResults | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);
  const debouncedQuery = useDebounce(query, 300);

  // Cmd+K / Ctrl+K to open
  useEffect(() => {
    function handler(e: KeyboardEvent) {
      if ((e.metaKey || e.ctrlKey) && e.key === "k") {
        e.preventDefault();
        setOpen((o) => !o);
      }
      if (e.key === "Escape") setOpen(false);
    }

    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, []);

  // Focus input when opened
  useEffect(() => {
    if (open) {
      setTimeout(() => inputRef.current?.focus(), 50);
    } else {
      setQuery("");
      setResults(null);
    }
  }, [open]);

  // Fetch results when query changes
  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setResults(null);
      return;
    }

    let cancelled = false;
    setIsLoading(true);

    search(debouncedQuery)
      .then((r) => {
        if (!cancelled) setResults(r);
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [debouncedQuery]);

  const hasResults = results && (results.users.length > 0 || results.courses.length > 0);
  const showEmpty = results !== null && !hasResults && debouncedQuery.length >= 2 && !isLoading;

  if (!open) {
    return (
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-body-sm text-neutral-500 hover:border-primary-300 hover:text-night-800 transition-colors"
        aria-label="Search (Ctrl+K)"
      >
        <SearchIcon className="h-4 w-4" />
        <span className="hidden sm:block">Search…</span>
        <kbd className="hidden rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-mono text-neutral-400 sm:block">
          ⌘K
        </kbd>
      </button>
    );
  }

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-night-900/40 backdrop-blur-sm"
        onClick={() => setOpen(false)}
      />

      {/* Panel */}
      <div className="relative w-full max-w-xl overflow-hidden rounded-card border border-neutral-200 bg-white shadow-2xl">
        {/* Search input */}
        <div className="flex items-center gap-3 border-b border-neutral-100 px-4 py-3">
          <SearchIcon className="h-4 w-4 shrink-0 text-neutral-400" />
          <input
            ref={inputRef}
            type="text"
            placeholder="Search users, courses…"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="flex-1 text-body-md text-night-900 placeholder:text-neutral-400 focus:outline-none"
          />
          {isLoading ? (
            <span className="h-4 w-4 animate-spin rounded-full border-2 border-primary-300 border-t-primary-600" />
          ) : null}
          <kbd className="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-mono text-neutral-400">
            Esc
          </kbd>
        </div>

        {/* Results */}
        <div className="max-h-96 overflow-y-auto">
          {showEmpty ? (
            <div className="px-4 py-8 text-center text-body-sm text-neutral-400">
              No results for &ldquo;{debouncedQuery}&rdquo;
            </div>
          ) : null}

          {results && results.users.length > 0 ? (
            <div>
              <p className="px-4 pb-1 pt-3 text-body-sm font-semibold uppercase tracking-wide text-neutral-400">
                Users
              </p>
              {results.users.map((user) => (
                <button
                  key={user.id}
                  type="button"
                  onClick={() => {
                    setOpen(false);
                    onNavigate(`/users/${user.id}/edit`);
                  }}
                  className="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-primary-50 transition-colors"
                >
                  <Avatar name={user.name} size="sm" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-body-sm font-medium text-night-900">{user.name}</p>
                    <p className="truncate text-body-sm text-neutral-500">{user.email}</p>
                  </div>
                  <Badge variant={STATUS_VARIANTS[user.status] ?? "neutral"} className="shrink-0">
                    {user.status}
                  </Badge>
                </button>
              ))}
            </div>
          ) : null}

          {results && results.courses.length > 0 ? (
            <div>
              <p className="px-4 pb-1 pt-3 text-body-sm font-semibold uppercase tracking-wide text-neutral-400">
                Courses
              </p>
              {results.courses.map((course) => (
                <button
                  key={course.id}
                  type="button"
                  onClick={() => {
                    setOpen(false);
                    onNavigate(`/courses/${course.id}/edit`);
                  }}
                  className="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-primary-50 transition-colors"
                >
                  <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 text-body-sm font-bold">
                    {course.title.charAt(0).toUpperCase()}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-body-sm font-medium text-night-900">{course.title}</p>
                    {course.short_description ? (
                      <p className="truncate text-body-sm text-neutral-500">{course.short_description}</p>
                    ) : null}
                  </div>
                  <Badge variant={STATUS_VARIANTS[course.status] ?? "neutral"} className="shrink-0">
                    {course.status}
                  </Badge>
                </button>
              ))}
            </div>
          ) : null}

          {!results && !isLoading && query.length < 2 && query.length > 0 ? (
            <div className="px-4 py-4 text-body-sm text-neutral-400">
              Type at least 2 characters to search…
            </div>
          ) : null}

          {!results && !isLoading && query.length === 0 ? (
            <div className="px-4 py-6 text-body-sm text-neutral-400">
              Search for users by name or email, or courses by title.
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
}

function SearchIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden="true">
      <circle cx="11" cy="11" r="8" />
      <path d="m21 21-4.35-4.35" />
    </svg>
  );
}
