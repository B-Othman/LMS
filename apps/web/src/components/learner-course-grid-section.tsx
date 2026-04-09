"use client";

import type { LearnerCourseListItem } from "@securecy/types";
import type { ReactNode } from "react";

import { EmptyState, Select } from "@securecy/ui";

import {
  learnerCourseSortOptions,
  type LearnerCourseSort,
} from "@/lib/learner-courses";

import { LearnerCourseCard } from "./learner-course-card";

interface LearnerCourseGridSectionProps {
  title: string;
  description: string;
  items: LearnerCourseListItem[];
  sortBy: LearnerCourseSort;
  isLoading: boolean;
  emptyTitle: string;
  emptyDescription: string;
  onSortChange: (value: LearnerCourseSort) => void;
  action?: ReactNode;
}

export function LearnerCourseGridSection({
  title,
  description,
  items,
  sortBy,
  isLoading,
  emptyTitle,
  emptyDescription,
  onSortChange,
  action,
}: LearnerCourseGridSectionProps) {
  return (
    <section>
      <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div className="max-w-2xl">
          <h2 className="text-h2 text-night-900">{title}</h2>
          <p className="mt-2 text-body-md text-neutral-500">{description}</p>
        </div>

        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
          {action}
          <div className="min-w-[220px] space-y-2">
            <label htmlFor="learner-course-sort" className="text-body-sm font-semibold text-night-800">
              Sort by
            </label>
            <Select
              id="learner-course-sort"
              value={sortBy}
              options={learnerCourseSortOptions.map((option) => ({
                label: option.label,
                value: option.value,
              }))}
              onChange={(event) => onSortChange(event.target.value as LearnerCourseSort)}
            />
          </div>
        </div>
      </div>

      {isLoading ? (
        <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
          {Array.from({ length: 3 }).map((_, index) => (
            <div
              key={index}
              className="h-[340px] animate-pulse rounded-card border border-neutral-200 bg-white shadow-card"
            />
          ))}
        </div>
      ) : items.length > 0 ? (
        <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
          {items.map((item) => (
            <LearnerCourseCard key={item.enrollment_id} item={item} />
          ))}
        </div>
      ) : (
        <div className="mt-6">
          <EmptyState title={emptyTitle} description={emptyDescription} />
        </div>
      )}
    </section>
  );
}
