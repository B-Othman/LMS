"use client";

import type { ReportExport } from "@securecy/types";
import { useEffect, useState } from "react";
import { Badge } from "@securecy/ui";
import { fetchExports } from "@/lib/reports";

const TYPE_LABELS: Record<string, string> = {
  completions: "Course Completions",
  learner_progress: "Learner Progress",
  assessments: "Assessments",
  course_detail: "Course Detail",
};

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function ExportsPanel() {
  const [exports, setExports] = useState<ReportExport[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchExports()
      .then(setExports)
      .catch(() => {})
      .finally(() => setIsLoading(false));
  }, []);

  return (
    <div className="rounded-card border border-neutral-200 bg-white">
      <div className="border-b border-neutral-100 px-5 py-3">
        <h3 className="text-body-md font-semibold text-night-900">Recent Exports</h3>
      </div>

      {isLoading ? (
        <div className="px-5 py-4 text-body-sm text-neutral-400">Loading…</div>
      ) : exports.length === 0 ? (
        <div className="px-5 py-6 text-center text-body-sm text-neutral-400">
          No exports yet. Use the Export buttons on each tab.
        </div>
      ) : (
        <div className="divide-y divide-neutral-100">
          {exports.map((e) => (
            <div key={e.id} className="flex items-center justify-between gap-3 px-5 py-3">
              <div className="min-w-0">
                <p className="text-body-sm font-medium text-night-900">
                  {TYPE_LABELS[e.report_type] ?? e.report_type}
                </p>
                <p className="text-body-sm text-neutral-500">
                  {e.format.toUpperCase()} &bull; {formatDate(e.created_at)}
                </p>
              </div>
              <div className="flex items-center gap-2">
                {e.status === "processing" ? (
                  <>
                    <span className="h-2 w-2 animate-pulse rounded-full bg-warning-500" />
                    <Badge variant="warning">Processing</Badge>
                  </>
                ) : e.status === "ready" && e.download_url ? (
                  <>
                    <Badge variant="success">Ready</Badge>
                    <a
                      href={e.download_url}
                      target="_blank"
                      rel="noreferrer"
                      className="text-body-sm font-medium text-primary-600 hover:underline"
                    >
                      Download
                    </a>
                  </>
                ) : (
                  <Badge variant="error">Failed</Badge>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
