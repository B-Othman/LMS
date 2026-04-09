"use client";

import type { LearnerCourseListItem } from "@securecy/types";
import { useEffect, useState, useTransition } from "react";

import { ProtectedRoute } from "@securecy/ui";

import {
  fetchLearnerCourses,
  type LearnerCourseSort,
} from "@/lib/learner-courses";

import { LearnerCourseGridSection } from "./learner-course-grid-section";

export function LearnerCoursesPage() {
  const [courses, setCourses] = useState<LearnerCourseListItem[]>([]);
  const [sortBy, setSortBy] = useState<LearnerCourseSort>("recently_accessed");
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    let cancelled = false;

    setIsLoading(true);
    setLoadError(null);

    fetchLearnerCourses(sortBy)
      .then((items) => {
        if (!cancelled) {
          setCourses(items);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setCourses([]);
          setLoadError("Your enrolled courses could not be loaded right now.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [sortBy]);

  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h1 className="text-h1 text-night-900">My Courses</h1>
            <p className="mt-2 max-w-3xl text-body-lg text-neutral-500">
              This page currently shows the courses already assigned to you. The self-enrollment
              catalog will expand here later.
            </p>
          </div>
        </div>

        <div className="mt-8 rounded-card border border-primary-100 bg-primary-50/70 px-5 py-4 text-body-md text-primary-800">
          Catalog browsing is reserved for a later release. For now, continue with the courses your
          team has assigned.
        </div>

        {loadError ? (
          <div className="mt-6 rounded-card border border-error-200 bg-error-50 px-4 py-3 text-body-md text-error-700">
            {loadError}
          </div>
        ) : null}

        <div className="mt-10">
          <LearnerCourseGridSection
            title="Assigned Courses"
            description="Sort your active enrollments by urgency or progress and jump straight back into a lesson."
            items={courses}
            sortBy={sortBy}
            isLoading={isLoading || isPending}
            emptyTitle="No assigned courses yet"
            emptyDescription="When an admin enrolls you into a course, it will appear here."
            onSortChange={(value) => startTransition(() => setSortBy(value))}
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
