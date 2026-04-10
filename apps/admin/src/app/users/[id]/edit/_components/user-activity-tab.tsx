"use client";

import type { AuditLog } from "@securecy/types";
import { useEffect, useState } from "react";
import { Avatar, EmptyState } from "@securecy/ui";
import { ActionBadge } from "@/components/action-badge";
import { AuditDiffViewer } from "@/components/audit-diff-viewer";
import { fetchUserAuditTrail } from "@/lib/audit";

function formatDateTime(iso: string): string {
  return new Date(iso).toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function UserActivityTab({ userId }: { userId: number }) {
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [expandedId, setExpandedId] = useState<number | null>(null);

  useEffect(() => {
    let cancelled = false;
    setIsLoading(true);

    fetchUserAuditTrail(userId, page)
      .then((res) => {
        if (!cancelled) {
          setLogs(res.data ?? []);
          setLastPage(res.meta?.last_page ?? 1);
          setTotal(res.meta?.total ?? 0);
        }
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [userId, page]);

  if (isLoading) {
    return (
      <div className="space-y-2">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="h-16 animate-pulse rounded-card bg-neutral-100" />
        ))}
      </div>
    );
  }

  if (logs.length === 0) {
    return (
      <EmptyState
        title="No activity recorded"
        description="Activity involving this user will appear here as actions are performed."
      />
    );
  }

  return (
    <div className="space-y-4">
      <div className="text-body-sm text-neutral-500">{total.toLocaleString()} activity entries</div>

      <div className="divide-y divide-neutral-100 overflow-hidden rounded-card border border-neutral-200 bg-white">
        {logs.map((log) => (
          <div key={log.id}>
            <div className="flex items-start gap-4 px-5 py-4">
              <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-100">
                {log.actor ? (
                  <Avatar name={log.actor.name} size="sm" />
                ) : (
                  <span className="text-body-sm text-neutral-400">S</span>
                )}
              </div>
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <ActionBadge action={log.action} />
                  {log.actor ? (
                    <span className="text-body-sm text-neutral-500">by {log.actor.name}</span>
                  ) : null}
                </div>
                <p className="mt-1 text-body-sm text-neutral-700">{log.description}</p>
                <div className="mt-1.5 flex items-center gap-3">
                  <span className="text-body-sm text-neutral-400">{formatDateTime(log.created_at)}</span>
                  {log.ip_address ? (
                    <span className="font-mono text-[11px] text-neutral-400">{log.ip_address}</span>
                  ) : null}
                </div>
              </div>
              {log.changes ? (
                <button
                  type="button"
                  onClick={() => setExpandedId(expandedId === log.id ? null : log.id)}
                  className="shrink-0 text-body-sm font-medium text-primary-600 hover:underline"
                >
                  {expandedId === log.id ? "Hide" : "Diff"}
                </button>
              ) : null}
            </div>

            {expandedId === log.id && log.changes ? (
              <div className="border-t border-neutral-100 bg-neutral-50 px-5 py-4">
                <AuditDiffViewer changes={log.changes} />
              </div>
            ) : null}
          </div>
        ))}
      </div>

      {lastPage > 1 ? (
        <div className="flex items-center justify-between text-body-sm text-neutral-500">
          <span>{total.toLocaleString()} entries</span>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage(page - 1)}
              className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
            >
              Previous
            </button>
            <span className="px-2 py-1.5">Page {page} of {lastPage}</span>
            <button
              type="button"
              disabled={page >= lastPage}
              onClick={() => setPage(page + 1)}
              className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
            >
              Next
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
