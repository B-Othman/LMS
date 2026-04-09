import type {
  LearnerCourseDetail,
  LearnerCourseListItem,
  LessonContent,
  LessonProgress,
} from "@securecy/types";

import { api } from "./api";

export type LearnerCourseSort = "recently_accessed" | "due_at" | "progress";

export const learnerCourseSortOptions = [
  { label: "Recently Accessed", value: "recently_accessed" },
  { label: "Due Date", value: "due_at" },
  { label: "Progress", value: "progress" },
] as const;

export async function fetchLearnerCourses(
  sortBy: LearnerCourseSort,
): Promise<LearnerCourseListItem[]> {
  const response = await api.get<LearnerCourseListItem[]>("/my/courses", {
    params: {
      sort_by: sortBy,
      sort_dir: getLearnerCourseSortDirection(sortBy),
    },
  });

  return response.data ?? [];
}

export async function fetchLearnerCourseDetail(
  courseId: string | number,
): Promise<LearnerCourseDetail> {
  const response = await api.get<LearnerCourseDetail>(`/my/courses/${courseId}`);

  if (!response.data) {
    throw new Error("The course detail response was empty.");
  }

  return response.data;
}

export async function fetchEnrollmentProgress(
  enrollmentId: string | number,
): Promise<LearnerCourseDetail> {
  const response = await api.get<LearnerCourseDetail>(`/my/enrollments/${enrollmentId}/progress`);

  if (!response.data) {
    throw new Error("The enrollment progress response was empty.");
  }

  return response.data;
}

export async function fetchLessonContent(
  lessonId: string | number,
): Promise<LessonContent> {
  const response = await api.get<LessonContent>(`/lessons/${lessonId}/content`);

  if (!response.data) {
    throw new Error("The lesson content response was empty.");
  }

  return response.data;
}

export async function startLesson(
  lessonId: string | number,
): Promise<LearnerCourseDetail> {
  const response = await api.post<LearnerCourseDetail>(`/my/lessons/${lessonId}/start`);

  if (!response.data) {
    throw new Error("The lesson start response was empty.");
  }

  return response.data;
}

export async function completeLesson(
  lessonId: string | number,
): Promise<LearnerCourseDetail> {
  const response = await api.post<LearnerCourseDetail>(`/my/lessons/${lessonId}/complete`);

  if (!response.data) {
    throw new Error("The lesson completion response was empty.");
  }

  return response.data;
}

export async function recordLessonHeartbeat(
  lessonId: string | number,
  seconds: number,
): Promise<LessonProgress> {
  const response = await api.post<LessonProgress>(`/my/lessons/${lessonId}/heartbeat`, {
    seconds,
  });

  if (!response.data) {
    throw new Error("The lesson heartbeat response was empty.");
  }

  return response.data;
}

export function getLearnerCourseSortDirection(
  sortBy: LearnerCourseSort,
): "asc" | "desc" {
  return sortBy === "due_at" ? "asc" : "desc";
}

export function formatLongDate(value: string | null): string {
  if (!value) {
    return "Not set";
  }

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
}

export function formatDurationMinutes(value: number): string {
  if (value <= 0) {
    return "Self-paced";
  }

  if (value < 60) {
    return `${value} min`;
  }

  const hours = Math.floor(value / 60);
  const minutes = value % 60;

  if (minutes === 0) {
    return `${hours} hr`;
  }

  return `${hours} hr ${minutes} min`;
}

export function buildCoursePlayerHref(
  courseId: number,
  lessonId: number | null | undefined,
): string {
  if (!lessonId) {
    return `/courses/${courseId}`;
  }

  return `/courses/${courseId}?lesson=${lessonId}`;
}
