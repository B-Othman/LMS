export type EnrollmentStatus = "active" | "completed" | "dropped" | "expired";
export type LessonProgressStatus = "not_started" | "in_progress" | "completed";

export interface EnrollmentProgressSummary {
  total_lessons: number;
  completed_lessons: number;
  in_progress_lessons: number;
  not_started_lessons: number;
  progress_percentage: number;
}

export interface EnrollmentUserSummary {
  id: number;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
}

export interface EnrollmentCourseSummary {
  id: number;
  title: string;
  slug: string;
  thumbnail_url: string | null;
  status: string;
}

export interface Enrollment {
  id: number;
  tenant_id: number;
  user_id: number;
  course_id: number;
  enrolled_by: number | null;
  status: EnrollmentStatus;
  enrolled_at: string;
  due_at: string | null;
  completed_at: string | null;
  progress_percentage: number;
  progress_summary?: EnrollmentProgressSummary | null;
  user?: EnrollmentUserSummary | null;
  course?: EnrollmentCourseSummary | null;
  created_at: string;
  updated_at: string;
}

export interface LessonProgress {
  id?: number;
  lesson_id: number;
  status: LessonProgressStatus;
  started_at: string | null;
  completed_at: string | null;
  progress_percentage: number;
  last_accessed_at: string | null;
  time_spent_seconds: number;
}

export interface LearnerLesson {
  id: number;
  module_id: number;
  title: string;
  type: string;
  duration_minutes: number | null;
  sort_order: number;
  is_previewable: boolean;
  progress: LessonProgress;
}

export interface LearnerModule {
  id: number;
  course_id: number;
  title: string;
  description: string | null;
  sort_order: number;
  lesson_count: number;
  total_duration: number;
  lessons: LearnerLesson[];
}

export interface LearnerCourseListItem {
  enrollment_id: number;
  status: EnrollmentStatus;
  enrolled_at: string;
  due_at: string | null;
  completed_at: string | null;
  next_lesson_id: number | null;
  last_accessed_lesson_id: number | null;
  progress_percentage: number;
  progress_summary: EnrollmentProgressSummary | null;
  course: {
    id: number;
    title: string;
    slug: string;
    short_description: string | null;
    thumbnail_url: string | null;
    status: string;
    visibility: string;
    module_count: number;
  } | null;
  updated_at: string;
}

export interface LearnerCourseDetail {
  enrollment: {
    id: number;
    status: EnrollmentStatus;
    enrolled_at: string;
    due_at: string | null;
    completed_at: string | null;
    progress_percentage: number;
    completed_lessons_count: number;
    progress_summary: EnrollmentProgressSummary | null;
    next_lesson_id: number | null;
    last_accessed_lesson_id: number | null;
  };
  course: {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    short_description: string | null;
    thumbnail_url: string | null;
    status: string;
    visibility: string;
    creator: {
      id: number;
      full_name: string;
    } | null;
    modules: LearnerModule[];
  };
}

export interface LessonContentMedia {
  id: number;
  original_filename: string;
  mime_type: string;
  size_bytes: number;
  url: string;
  download_url: string;
  thumbnail_url: string | null;
  metadata: Record<string, unknown> | null;
}

export interface LessonContent {
  id: number;
  module_id: number;
  title: string;
  type: string;
  duration_minutes: number | null;
  content_html: string | null;
  content_url: string | null;
  download_url: string | null;
  mime_type: string | null;
  metadata: Record<string, unknown> | null;
  media: LessonContentMedia | null;
}

export interface EnrollmentListFilters {
  search?: string;
  status?: EnrollmentStatus | "";
  course_id?: number | "";
  user_id?: number | "";
  sort_by?: "enrolled_at" | "due_at" | "status" | "created_at";
  sort_dir?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export interface CreateEnrollmentPayload {
  course_id: number;
  user_id?: number;
  user_ids?: number[];
  due_at?: string | null;
}

export interface BatchEnrollmentResult {
  success_count: number;
  failure_count: number;
  failures: Array<{
    user_id: number;
    message: string;
  }>;
  enrollments: Array<{
    id: number;
    user_id: number;
    course_id: number;
    status: EnrollmentStatus;
  }>;
}
