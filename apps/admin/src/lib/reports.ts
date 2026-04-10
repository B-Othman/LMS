import type {
  AssessmentRow,
  CompletionRow,
  CourseDetailReport,
  CreateExportPayload,
  LearnerProgressRow,
  OverviewStats,
  PaginatedResponse,
  QuestionBreakdownRow,
  ReportExport,
} from "@securecy/types";

import { api } from "./api";

export async function fetchOverviewStats(): Promise<OverviewStats> {
  const res = await api.get<OverviewStats>("/reports/overview");

  if (!res.data) throw new Error("Failed to load overview stats.");

  return res.data;
}

export async function fetchCompletionReport(params: Record<string, string | number | undefined> = {}): Promise<PaginatedResponse<CompletionRow>> {
  return api.paginated<CompletionRow>("/reports/completions", { params });
}

export async function fetchLearnerProgressReport(params: Record<string, string | number | undefined> = {}): Promise<PaginatedResponse<LearnerProgressRow>> {
  return api.paginated<LearnerProgressRow>("/reports/learner-progress", { params });
}

export async function fetchAssessmentReport(params: Record<string, string | number | undefined> = {}): Promise<PaginatedResponse<AssessmentRow>> {
  return api.paginated<AssessmentRow>("/reports/assessments", { params });
}

export async function fetchQuestionBreakdown(quizId: number): Promise<QuestionBreakdownRow[]> {
  const res = await api.get<QuestionBreakdownRow[]>(`/reports/assessments/${quizId}/questions`);

  return res.data ?? [];
}

export async function fetchCourseDetailReport(courseId: number): Promise<CourseDetailReport> {
  const res = await api.get<CourseDetailReport>(`/reports/courses/${courseId}/detail`);

  if (!res.data) throw new Error("Failed to load course detail.");

  return res.data;
}

export async function fetchExports(): Promise<ReportExport[]> {
  const res = await api.get<ReportExport[]>("/reports/exports");

  return res.data ?? [];
}

export async function createExport(payload: CreateExportPayload): Promise<ReportExport> {
  const res = await api.post<ReportExport>("/reports/exports", payload);

  if (!res.data) throw new Error("Failed to start export.");

  return res.data;
}

export async function fetchExportById(id: number): Promise<ReportExport> {
  const res = await api.get<ReportExport>(`/reports/exports/${id}`);

  if (!res.data) throw new Error("Export not found.");

  return res.data;
}

/**
 * Poll an export every 2s until it reaches ready or failed status (max 60s).
 */
export async function pollExport(id: number): Promise<ReportExport> {
  const maxAttempts = 30;

  for (let i = 0; i < maxAttempts; i++) {
    await sleep(2000);
    const exported = await fetchExportById(id);

    if (exported.status === "ready" || exported.status === "failed") {
      return exported;
    }
  }

  throw new Error("Export timed out.");
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}
