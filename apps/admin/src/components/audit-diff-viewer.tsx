import type { AuditLogChanges } from "@securecy/types";

interface AuditDiffViewerProps {
  changes: AuditLogChanges | Record<string, unknown> | null;
}

function formatValue(v: unknown): string {
  if (v === null || v === undefined) return "—";
  if (typeof v === "object") return JSON.stringify(v, null, 2);
  return String(v);
}

function isDiff(changes: AuditLogChanges | Record<string, unknown>): changes is AuditLogChanges {
  return "before" in changes && "after" in changes;
}

export function AuditDiffViewer({ changes }: AuditDiffViewerProps) {
  if (!changes) {
    return <span className="text-body-sm text-neutral-400">No changes recorded</span>;
  }

  if (isDiff(changes)) {
    const keys = Array.from(
      new Set([...Object.keys(changes.before), ...Object.keys(changes.after)]),
    );

    if (keys.length === 0) {
      return <span className="text-body-sm text-neutral-400">No field changes</span>;
    }

    return (
      <div className="overflow-hidden rounded-lg border border-neutral-200 text-body-sm">
        <div className="grid grid-cols-[120px_1fr_1fr] border-b border-neutral-200 bg-neutral-50">
          <div className="px-3 py-2 font-semibold text-neutral-500">Field</div>
          <div className="border-l border-neutral-200 px-3 py-2 font-semibold text-error-700">Before</div>
          <div className="border-l border-neutral-200 px-3 py-2 font-semibold text-success-700">After</div>
        </div>
        {keys.map((key) => {
          const before = changes.before[key];
          const after = changes.after[key];
          const changed = JSON.stringify(before) !== JSON.stringify(after);

          return (
            <div
              key={key}
              className={`grid grid-cols-[120px_1fr_1fr] border-b border-neutral-100 last:border-0 ${changed ? "bg-warning-50/40" : ""}`}
            >
              <div className="px-3 py-2 font-mono text-[11px] text-neutral-600 break-all">{key}</div>
              <div className="border-l border-neutral-100 px-3 py-2 text-error-800 break-all whitespace-pre-wrap font-mono text-[11px]">
                {formatValue(before)}
              </div>
              <div className="border-l border-neutral-100 px-3 py-2 text-success-800 break-all whitespace-pre-wrap font-mono text-[11px]">
                {formatValue(after)}
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  // Plain JSON payload (e.g. role_ids, simple metadata)
  return (
    <pre className="overflow-auto rounded-lg bg-neutral-50 p-3 text-[11px] font-mono text-neutral-700 max-h-48">
      {JSON.stringify(changes, null, 2)}
    </pre>
  );
}
