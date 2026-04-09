"use client";

interface CompletionCheckmarkProps {
  visible: boolean;
}

export function CompletionCheckmark({ visible }: CompletionCheckmarkProps) {
  return (
    <div
      className={`pointer-events-none fixed bottom-6 right-6 z-30 flex items-center gap-3 rounded-2xl border border-success-200 bg-white px-4 py-3 shadow-card transition-all duration-300 ${
        visible ? "translate-y-0 opacity-100" : "translate-y-3 opacity-0"
      }`}
      aria-hidden={!visible}
    >
      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-success-50 text-success-700">
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2.2">
          <path d="m5 13 4 4L19 7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </div>
      <div>
        <p className="text-body-sm font-semibold text-night-900">Lesson completed</p>
        <p className="text-body-sm text-neutral-500">Progress has been saved.</p>
      </div>
    </div>
  );
}
