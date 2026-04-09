"use client";

import type { QuizAttempt, QuizAttemptQuestion } from "@securecy/types";

import { Badge, Button, Card } from "@securecy/ui";

interface QuizResultsProps {
  attempt: QuizAttempt;
  canRetry: boolean;
  isRetrying?: boolean;
  onRetry?: () => void;
}

export function QuizResults({
  attempt,
  canRetry,
  isRetrying = false,
  onRetry,
}: QuizResultsProps) {
  const statusLabel = attempt.status === "needs_grading"
    ? "Awaiting Review"
    : attempt.passed
      ? "Passed"
      : "Not Passed";
  const statusVariant = attempt.status === "needs_grading"
    ? "warning"
    : attempt.passed
      ? "success"
      : "error";

  return (
    <div className="space-y-5">
      <Card>
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
              Quiz Results
            </p>
            <h3 className="mt-3 text-h2 text-night-900">{attempt.quiz.title}</h3>
            <p className="mt-2 text-body-md text-neutral-500">
              {attempt.results_available
                ? "Review your performance and question breakdown below."
                : attempt.status === "needs_grading"
                  ? "Your submission has been recorded and is waiting for manual review."
                  : "Your submission was recorded. Results are hidden for this quiz."}
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <Badge variant={statusVariant}>{statusLabel}</Badge>
            {attempt.results_available ? (
              <Badge variant="info">
                Score: {attempt.score ?? 0}%
              </Badge>
            ) : null}
          </div>
        </div>

        {attempt.results_available ? (
          <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <MetricCard label="Score" value={`${attempt.score ?? 0}%`} />
            <MetricCard label="Pass Mark" value={`${attempt.quiz.pass_score}%`} />
            <MetricCard label="Time Spent" value={formatTimeSpent(attempt.time_spent_seconds)} />
          </div>
        ) : (
          <div className="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-4 text-body-md text-neutral-600">
            {attempt.status === "needs_grading"
              ? "Short-answer grading is pending. Your final score will appear after review."
              : "This quiz is configured to hide scored feedback from learners after submission."}
          </div>
        )}

        {canRetry && onRetry ? (
          <div className="mt-6">
            <Button type="button" onClick={onRetry} disabled={isRetrying}>
              {isRetrying ? "Starting..." : "Try Again"}
            </Button>
          </div>
        ) : null}
      </Card>

      {attempt.results_available ? (
        <div className="space-y-4">
          {attempt.questions.map((question, index) => (
            <QuestionBreakdown key={question.id} question={question} index={index} />
          ))}
        </div>
      ) : null}
    </div>
  );
}

function QuestionBreakdown({
  question,
  index,
}: {
  question: QuizAttemptQuestion;
  index: number;
}) {
  const selectedOptionIds = question.answer.answer_payload.selected_option_ids ?? [];
  const selectedLabels = question.question_type === "short_answer"
    ? [question.answer.answer_payload.text?.trim() || "No answer submitted"]
    : question.options
        .filter((option) => selectedOptionIds.includes(option.id))
        .map((option) => option.label);
  const correctLabels = question.options
    .filter((option) => option.is_correct)
    .map((option) => option.label);

  return (
    <Card>
      <div className="flex flex-wrap items-center gap-2">
        <Badge variant={question.answer.is_correct ? "success" : "error"}>
          {question.answer.is_correct ? "Correct" : "Incorrect"}
        </Badge>
        <Badge variant="neutral">
          Question {index + 1}
        </Badge>
      </div>

      <div
        className="mt-4 text-body-lg font-semibold leading-7 text-night-900"
        dangerouslySetInnerHTML={{ __html: question.prompt }}
      />

      <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <BreakdownCard
          label="Your Answer"
          items={selectedLabels.length ? selectedLabels : ["No answer submitted"]}
        />
        <BreakdownCard
          label="Correct Answer"
          items={correctLabels.length ? correctLabels : ["Manual grading required"]}
        />
      </div>

      {question.explanation ? (
        <div className="mt-5 rounded-2xl border border-primary-100 bg-primary-50 px-4 py-4">
          <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-primary-700">
            Explanation
          </p>
          <div
            className="mt-2 text-body-md leading-7 text-night-900"
            dangerouslySetInnerHTML={{ __html: question.explanation }}
          />
        </div>
      ) : null}
    </Card>
  );
}

function BreakdownCard({
  label,
  items,
}: {
  label: string;
  items: string[];
}) {
  return (
    <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-4">
      <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
        {label}
      </p>
      <ul className="mt-3 space-y-2 text-body-md text-night-900">
        {items.map((item, index) => (
          <li key={`${label}-${index}`} className="rounded-xl bg-white px-3 py-2">
            {item}
          </li>
        ))}
      </ul>
    </div>
  );
}

function MetricCard({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-4">
      <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
        {label}
      </p>
      <p className="mt-3 text-h3 text-night-900">{value}</p>
    </div>
  );
}

function formatTimeSpent(value: number): string {
  if (value < 60) {
    return `${value}s`;
  }

  const minutes = Math.floor(value / 60);
  const seconds = value % 60;

  if (seconds === 0) {
    return `${minutes}m`;
  }

  return `${minutes}m ${seconds}s`;
}
