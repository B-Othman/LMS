"use client";

import type { LearnerModule, LessonProgressStatus } from "@securecy/types";
import { useEffect, useState } from "react";

interface LessonSidebarProps {
  courseTitle: string;
  modules: LearnerModule[];
  currentLessonId: number;
  onSelectLesson: (lessonId: number) => void;
}

export function LessonSidebar({
  courseTitle,
  modules,
  currentLessonId,
  onSelectLesson,
}: LessonSidebarProps) {
  const [openModules, setOpenModules] = useState<Record<number, boolean>>(() =>
    Object.fromEntries(modules.map((module) => [module.id, true])),
  );

  useEffect(() => {
    setOpenModules((current) => {
      const next = { ...current };

      for (const module of modules) {
        if (!(module.id in next)) {
          next[module.id] = true;
        }
      }

      const currentModule = modules.find((module) =>
        module.lessons.some((lesson) => lesson.id === currentLessonId),
      );

      if (currentModule) {
        next[currentModule.id] = true;
      }

      return next;
    });
  }, [currentLessonId, modules]);

  return (
    <aside className="rounded-[28px] border border-neutral-200 bg-white shadow-card">
      <div className="border-b border-neutral-200 px-5 py-5">
        <p className="text-overline uppercase tracking-[0.22em] text-primary-700">Course Player</p>
        <h2 className="mt-3 text-h3 text-night-900">{courseTitle}</h2>
      </div>

      <div className="max-h-[calc(100vh-14rem)] overflow-y-auto px-3 py-3">
        {modules.map((module, index) => {
          const isOpen = openModules[module.id] ?? true;

          return (
            <div key={module.id} className="mb-3 rounded-2xl border border-neutral-200 bg-neutral-50">
              <button
                type="button"
                onClick={() =>
                  setOpenModules((current) => ({
                    ...current,
                    [module.id]: !isOpen,
                  }))
                }
                className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
              >
                <div>
                  <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-primary-700">
                    Module {index + 1}
                  </p>
                  <p className="mt-1 text-body-md font-semibold text-night-900">{module.title}</p>
                </div>
                <ChevronIcon open={isOpen} />
              </button>

              {isOpen ? (
                <div className="space-y-1 border-t border-neutral-200 px-2 py-2">
                  {module.lessons.map((lesson) => {
                    const isCurrent = lesson.id === currentLessonId;

                    return (
                      <button
                        key={lesson.id}
                        type="button"
                        onClick={() => onSelectLesson(lesson.id)}
                        className={`flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition-colors ${
                          isCurrent
                            ? "bg-primary-50 text-primary-800"
                            : "text-neutral-700 hover:bg-white"
                        }`}
                        aria-current={isCurrent ? "true" : undefined}
                      >
                        <LessonStatusIcon status={lesson.progress.status} />
                        <div className="min-w-0">
                          <p className={`text-body-md font-semibold ${isCurrent ? "text-primary-800" : "text-night-900"}`}>
                            {lesson.title}
                          </p>
                          <p className="mt-1 text-body-sm text-neutral-500">
                            {formatLessonType(lesson.type)}
                          </p>
                        </div>
                      </button>
                    );
                  })}
                </div>
              ) : null}
            </div>
          );
        })}
      </div>
    </aside>
  );
}

function ChevronIcon({ open }: { open: boolean }) {
  return (
    <svg
      viewBox="0 0 24 24"
      className={`h-4 w-4 text-neutral-500 transition-transform ${open ? "rotate-180" : ""}`}
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
    >
      <path d="m6 9 6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function LessonStatusIcon({ status }: { status: LessonProgressStatus }) {
  if (status === "completed") {
    return (
      <span className="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-success-50 text-success-700">
        <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2.2">
          <path d="m5 13 4 4L19 7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </span>
    );
  }

  if (status === "in_progress") {
    return (
      <span className="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700">
        <svg viewBox="0 0 24 24" className="h-4 w-4" fill="currentColor">
          <path d="M8 6.5v11l8-5.5-8-5.5Z" />
        </svg>
      </span>
    );
  }

  return <span className="mt-0.5 inline-flex h-6 w-6 shrink-0 rounded-full border-2 border-neutral-300" />;
}

function formatLessonType(value: string): string {
  if (value === "scorm") return "SCORM";
  return value
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}
