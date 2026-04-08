export type EnrollmentStatus = "active" | "completed" | "dropped" | "expired";

export interface Enrollment {
  id: number;
  tenantId: number;
  userId: number;
  courseId: number;
  status: EnrollmentStatus;
  enrolledAt: string;
  completedAt: string | null;
  expiresAt: string | null;
  progressPercent: number;
  createdAt: string;
  updatedAt: string;
}

export interface LessonProgress {
  id: number;
  enrollmentId: number;
  lessonId: number;
  status: "not_started" | "in_progress" | "completed";
  startedAt: string | null;
  completedAt: string | null;
  createdAt: string;
  updatedAt: string;
}
