"use client";

import type { LearnerCourseListItem } from "@securecy/types";
import Link from "next/link";

import { Card, ProgressBar } from "@securecy/ui";

import { buildCoursePlayerHref } from "@/lib/learner-courses";

import { LearnerStatusBadge } from "./learner-status-badge";

interface LearnerCourseCardProps {
  item: LearnerCourseListItem;
}

export function LearnerCourseCard({ item }: LearnerCourseCardProps) {
  const course = item.course;

  if (!course) {
    return null;
  }

  return (
    <Card className="flex h-full flex-col overflow-hidden p-0">
      <div className="relative h-40 overflow-hidden bg-primary-100">
        {course.thumbnail_url ? (
          <img
            src={course.thumbnail_url}
            alt={course.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(91,146,198,0.35),_rgba(232,240,248,0.95)_55%,_rgba(255,255,255,1))] px-6 text-center">
            <p className="text-body-md font-semibold text-primary-700">{course.title}</p>
          </div>
        )}
      </div>

      <div className="flex flex-1 flex-col gap-4 p-5">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h3 className="text-h4 text-night-900">{course.title}</h3>
            {course.short_description ? (
              <p className="mt-2 line-clamp-2 text-body-sm text-neutral-500">{course.short_description}</p>
            ) : null}
          </div>
          <LearnerStatusBadge status={item.status} />
        </div>

        <ProgressBar
          value={item.progress_percentage}
          label="Progress"
          className="mt-auto"
        />

        <div className="flex items-center justify-between gap-3 text-body-sm text-neutral-500">
          <span>{course.module_count} {course.module_count === 1 ? "module" : "modules"}</span>
          <span>{formatDueDate(item.due_at)}</span>
        </div>

        <Link
          href={buildCoursePlayerHref(course.id, item.next_lesson_id)}
          className="mt-1 inline-flex w-full items-center justify-center rounded-lg bg-primary-500 px-4 py-2 text-button text-white transition-colors hover:bg-primary-300 active:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
        >
          Continue
        </Link>
      </div>
    </Card>
  );
}

function formatDueDate(value: string | null): string {
  if (!value) {
    return "No due date";
  }

  return `Due ${new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value))}`;
}
