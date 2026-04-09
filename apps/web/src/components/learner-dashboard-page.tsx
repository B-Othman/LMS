"use client";

import type { LearnerCourseListItem } from "@securecy/types";
import { useEffect, useMemo, useState, useTransition } from "react";

import {
  CertificatesIcon,
  CoursesIcon,
  ProtectedRoute,
  StatsCard,
  useAuth,
} from "@securecy/ui";

import {
  fetchLearnerCourses,
  type LearnerCourseSort,
} from "@/lib/learner-courses";

import { LearnerCourseGridSection } from "./learner-course-grid-section";

export function LearnerDashboardPage() {
  const { user } = useAuth();
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
          setLoadError("Your dashboard could not be loaded right now.");
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

  const stats = useMemo(() => {
    const inProgress = courses.filter((item) => item.status === "active").length;
    const completed = courses.filter((item) => item.status === "completed").length;

    return {
      inProgress,
      completed,
      certificates: 0,
    };
  }, [courses]);

  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="rounded-card border border-primary-100 bg-[radial-gradient(circle_at_top_left,_rgba(91,146,198,0.18),_rgba(255,255,255,0.98)_58%),linear-gradient(135deg,_#ffffff_0%,_#f7fafc_100%)] p-6 shadow-card">
          <p className="text-overline uppercase tracking-[0.22em] text-primary-700">Learner Dashboard</p>
          <h1 className="mt-3 text-h1 text-night-900">
            Welcome back, {user?.first_name ?? "Learner"}
          </h1>
          <p className="mt-3 max-w-3xl text-body-lg text-neutral-600">
            Pick up where you left off, review upcoming deadlines, and keep your course work moving.
          </p>
        </div>

        <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
          <StatsCard
            icon={<CoursesIcon className="h-6 w-6" />}
            value={stats.inProgress}
            label="In Progress"
            accentClassName="bg-primary-50 text-primary-700"
          />
          <StatsCard
            icon={<CoursesIcon className="h-6 w-6" />}
            value={stats.completed}
            label="Completed"
            accentClassName="bg-success-50 text-success-700"
          />
          <StatsCard
            icon={<CertificatesIcon className="h-6 w-6" />}
            value={stats.certificates}
            label="Certificates"
            accentClassName="bg-warning-50 text-warning-700"
          />
        </div>

        {loadError ? (
          <div className="mt-8 rounded-card border border-error-200 bg-error-50 px-4 py-3 text-body-md text-error-700">
            {loadError}
          </div>
        ) : null}

        <div className="mt-10">
          <LearnerCourseGridSection
            title="My Courses"
            description="Your assigned courses are sorted around the next thing you should act on."
            items={courses}
            sortBy={sortBy}
            isLoading={isLoading || isPending}
            emptyTitle="No courses assigned yet"
            emptyDescription="Your assigned learning paths will appear here as soon as an admin enrolls you."
            onSortChange={(value) => startTransition(() => setSortBy(value))}
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
