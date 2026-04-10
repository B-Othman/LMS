"use client";

import type { AssessmentRow, QuestionBreakdownRow } from "@securecy/types";
import { useCallback, useState } from "react";
import { Badge, Button, DataTable, type DataTableColumn, EmptyState, useToast } from "@securecy/ui";
import { ExportButton } from "@/components/export-button";
import { HeatmapBar } from "@/components/heatmap-bar";
import { fetchAssessmentReport, fetchQuestionBreakdown } from "@/lib/reports";
import { useReport } from "./use-report";

export function AssessmentsTab() {
  const { showToast } = useToast();
  const [expandedQuizId, setExpandedQuizId] = useState<number | null>(null);
  const [questions, setQuestions] = useState<QuestionBreakdownRow[]>([]);
  const [loadingQuiz, setLoadingQuiz] = useState(false);

  const fetcher = useCallback(() => fetchAssessmentReport(), []);
  const { rows, meta, isLoading, page, setPage } = useReport(fetcher, []);

  async function handleExpandQuiz(quizId: number) {
    if (expandedQuizId === quizId) {
      setExpandedQuizId(null);
      return;
    }

    setLoadingQuiz(true);
    try {
      const qs = await fetchQuestionBreakdown(quizId);
      setQuestions(qs);
      setExpandedQuizId(quizId);
    } catch {
      showToast({ tone: "error", message: "Failed to load question breakdown." });
    } finally {
      setLoadingQuiz(false);
    }
  }

  const columns: DataTableColumn<AssessmentRow>[] = [
    {
      key: "title",
      header: "Quiz",
      render: (r) => (
        <div>
          <p className="font-medium text-night-900">{r.title}</p>
          <p className="text-body-sm text-neutral-500">{r.course_title}</p>
        </div>
      ),
    },
    {
      key: "total_attempts",
      header: "Attempts",
      render: (r) => <span>{r.total_attempts.toLocaleString()}</span>,
    },
    {
      key: "avg_score",
      header: "Avg Score",
      render: (r) => <span>{r.avg_score != null ? `${r.avg_score}%` : "—"}</span>,
    },
    {
      key: "pass_rate",
      header: "Pass Rate",
      render: (r) =>
        r.pass_rate != null ? (
          <div className="min-w-28">
            <HeatmapBar value={r.pass_rate} />
          </div>
        ) : (
          <span className="text-neutral-400">—</span>
        ),
    },
    {
      key: "highest_score",
      header: "Highest",
      render: (r) => (
        <span className="text-success-700">{r.highest_score != null ? `${r.highest_score}%` : "—"}</span>
      ),
    },
    {
      key: "lowest_score",
      header: "Lowest",
      render: (r) => (
        <span className="text-error-700">{r.lowest_score != null ? `${r.lowest_score}%` : "—"}</span>
      ),
    },
    {
      key: "actions",
      header: "",
      render: (r) => (
        <button
          type="button"
          onClick={() => void handleExpandQuiz(r.id)}
          className="text-body-sm font-medium text-primary-600 hover:underline"
        >
          {expandedQuizId === r.id ? "Hide" : "Questions"}
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-4">
      <div className="flex justify-end gap-2">
        <ExportButton payload={{ report_type: "assessments", format: "csv" }} label="Export CSV" />
        <ExportButton payload={{ report_type: "assessments", format: "pdf" }} label="Export PDF" />
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-14 animate-pulse rounded-lg bg-neutral-100" />
          ))}
        </div>
      ) : rows.length === 0 ? (
        <EmptyState title="No assessment data" description="Quiz attempts will appear here once learners start taking assessments." />
      ) : (
        <>
          <DataTable<AssessmentRow> columns={columns} rows={rows} rowKey={(r) => r.id} />

          {/* Question breakdown panel */}
          {expandedQuizId !== null ? (
            <div className="overflow-hidden rounded-card border border-primary-100 bg-primary-50/40">
              <div className="border-b border-primary-100 px-5 py-3">
                <p className="text-body-sm font-semibold text-night-900">Question-level breakdown</p>
              </div>
              {loadingQuiz ? (
                <div className="px-5 py-4 text-body-sm text-neutral-400">Loading questions…</div>
              ) : questions.length === 0 ? (
                <div className="px-5 py-4 text-body-sm text-neutral-400">No question data available yet.</div>
              ) : (
                <div className="divide-y divide-primary-100">
                  {questions.map((q, i) => (
                    <div
                      key={q.id}
                      className={`px-5 py-3 ${q.correct_rate < 50 ? "bg-warning-50/60" : ""}`}
                    >
                      <div className="flex items-start gap-3">
                        <span className="mt-0.5 shrink-0 text-body-sm font-semibold text-neutral-400">
                          Q{i + 1}
                        </span>
                        <div className="min-w-0 flex-1">
                          <p className="text-body-sm text-night-800 line-clamp-2">{q.prompt}</p>
                          <div className="mt-2 flex items-center gap-3">
                            <HeatmapBar value={q.correct_rate} height={6} />
                            <span className="shrink-0 text-body-sm text-neutral-500">
                              {q.correct_count}/{q.total_answers} correct
                            </span>
                          </div>
                        </div>
                        {q.correct_rate < 50 ? (
                          <Badge variant="warning">Hard</Badge>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ) : null}
        </>
      )}

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between text-body-sm text-neutral-500">
          <span>{meta.total.toLocaleString()} quizzes</span>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage(page - 1)}
              className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
            >
              Previous
            </button>
            <span className="px-2 py-1.5">Page {page} of {meta.last_page}</span>
            <button
              type="button"
              disabled={page >= meta.last_page}
              onClick={() => setPage(page + 1)}
              className="rounded-lg border border-neutral-200 px-3 py-1.5 hover:bg-neutral-50 disabled:opacity-40"
            >
              Next
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
