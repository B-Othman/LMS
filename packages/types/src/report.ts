export type ReportType = "completions" | "learner_progress" | "assessments" | "course_detail";
export type ExportFormat = "csv" | "pdf";
export type ExportStatus = "processing" | "ready" | "failed";

export interface OverviewStats {
  total_users: number;
  total_courses: number;
  total_enrollments: number;
  enrollments_this_month: number;
  total_completions: number;
  completions_this_month: number;
  avg_completion_rate: number;
  total_certificates: number;
  completions_by_month: Array<{ month: string; count: number }>;
  top_courses: Array<{ id: number; title: string; enrollment_count: number }>;
  recent_enrollments: Array<{
    id: number;
    learner_name: string;
    course_title: string;
    enrolled_at: string;
  }>;
}

export interface CompletionRow {
  id: number;
  title: string;
  category_name: string | null;
  total_enrolled: number;
  completed_count: number;
  completion_rate: number;
  avg_days_to_complete: number | null;
}

export interface LearnerProgressRow {
  id: number;
  name: string;
  email: string;
  enrolled_count: number;
  completed_count: number;
  in_progress_count: number;
  avg_score: number | null;
}

export interface AssessmentRow {
  id: number;
  title: string;
  course_title: string;
  pass_score: number;
  total_attempts: number;
  avg_score: number | null;
  pass_rate: number | null;
  highest_score: number | null;
  lowest_score: number | null;
}

export interface QuestionBreakdownRow {
  id: number;
  prompt: string;
  total_answers: number;
  correct_count: number;
  correct_rate: number;
}

export interface CourseDetailReport {
  overview: {
    id: number;
    title: string;
    total_enrolled: number;
    completed_count: number;
    completion_rate: number;
    avg_days_to_complete: number | null;
  } | null;
  enrollment_timeline: Array<{ month: string; count: number; cumulative: number }>;
  lesson_completion: Array<{
    lesson_id: number;
    title: string;
    module_title: string;
    completed_count: number;
    total_enrolled: number;
    completion_rate: number;
  }>;
  dropoff: Array<{
    lesson_id: number;
    title: string;
    reached_count: number;
    reach_rate: number;
  }>;
}

export interface ReportExport {
  id: number;
  report_type: ReportType;
  format: ExportFormat;
  status: ExportStatus;
  filters: Record<string, unknown>;
  download_url: string | null;
  error_message: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface CreateExportPayload {
  report_type: ReportType;
  format: ExportFormat;
  filters?: {
    course_id?: number;
    category_id?: number;
    quiz_id?: number;
    date_from?: string;
    date_to?: string;
    search?: string;
  };
}
