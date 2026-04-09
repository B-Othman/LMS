"use client";

import type {
  QuizAttempt,
  QuizAttemptAnswerPayload,
  QuizSummary,
} from "@securecy/types";
import { useEffect, useMemo, useState } from "react";

import { Alert, Badge, Button, Card, Modal } from "@securecy/ui";

import {
  fetchQuizAttempt,
  startQuizAttempt,
  submitQuizAttempt,
} from "@/lib/quizzes";

import { QuestionCard } from "./question-card";
import { QuizResults } from "./quiz-results";
import { QuizTimer } from "./quiz-timer";

interface QuizLessonPlayerProps {
  quiz: QuizSummary;
  canTrackProgress: boolean;
  onRefreshCourseDetail: () => Promise<void>;
}

type ViewMode = "all" | "single";

export function QuizLessonPlayer({
  quiz,
  canTrackProgress,
  onRefreshCourseDetail,
}: QuizLessonPlayerProps) {
  const [attempt, setAttempt] = useState<QuizAttempt | null>(null);
  const [isLoadingAttempt, setIsLoadingAttempt] = useState(false);
  const [isStarting, setIsStarting] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [answers, setAnswers] = useState<Record<number, QuizAttemptAnswerPayload>>({});
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [viewMode, setViewMode] = useState<ViewMode>("all");
  const [activeQuestionIndex, setActiveQuestionIndex] = useState(0);

  useEffect(() => {
    setAttempt(null);
    setAnswers({});
    setError(null);
    setActiveQuestionIndex(0);
  }, [quiz.id]);

  useEffect(() => {
    let cancelled = false;

    if (!quiz.latest_attempt || quiz.latest_attempt.status !== "in_progress") {
      return;
    }

    setIsLoadingAttempt(true);
    setError(null);

    fetchQuizAttempt(quiz.latest_attempt.id)
      .then((response) => {
        if (cancelled) {
          return;
        }

        setAttempt(response);
        setAnswers(toAnswerMap(response));
      })
      .catch(() => {
        if (!cancelled) {
          setAttempt(null);
          setAnswers({});
          setError("The in-progress quiz attempt could not be restored.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLoadingAttempt(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [quiz.id, quiz.latest_attempt?.id, quiz.latest_attempt?.status]);

  const visibleQuestions = useMemo(() => {
    if (!attempt) {
      return [];
    }

    if (viewMode === "single") {
      return attempt.questions[activeQuestionIndex]
        ? [attempt.questions[activeQuestionIndex]]
        : [];
    }

    return attempt.questions;
  }, [activeQuestionIndex, attempt, viewMode]);

  const attemptsRemainingLabel = quiz.attempts_remaining === null || quiz.attempts_remaining === undefined
    ? "Unlimited attempts"
    : `${quiz.attempts_remaining} attempt${quiz.attempts_remaining === 1 ? "" : "s"} remaining`;

  const canRetry = canTrackProgress
    && (quiz.attempts_remaining === null || quiz.attempts_remaining === undefined || quiz.attempts_remaining > 0);

  async function handleStart() {
    setIsStarting(true);
    setError(null);

    try {
      const nextAttempt = await startQuizAttempt(quiz.id);
      setAttempt(nextAttempt);
      setAnswers(toAnswerMap(nextAttempt));
      setActiveQuestionIndex(0);
    } catch {
      setError("The quiz could not be started.");
    } finally {
      setIsStarting(false);
    }
  }

  async function handleSubmit() {
    if (!attempt) {
      return;
    }

    setIsSubmitting(true);
    setError(null);
    setConfirmOpen(false);

    try {
      const submittedAttempt = await submitQuizAttempt(attempt.id, {
        answers: attempt.questions.map((question) => ({
          question_id: question.id,
          answer_payload: answers[question.id] ?? {},
        })),
      });

      setAttempt(submittedAttempt);
      await onRefreshCourseDetail();
    } catch {
      setError("The quiz could not be submitted.");
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!canTrackProgress) {
    return (
      <Alert tone="error">
        This enrollment is no longer active, so quiz attempts are unavailable.
      </Alert>
    );
  }

  if (isLoadingAttempt) {
    return (
      <div className="space-y-4">
        <div className="h-16 animate-pulse rounded-3xl bg-neutral-100" />
        <div className="h-64 animate-pulse rounded-3xl bg-neutral-100" />
      </div>
    );
  }

  if (!attempt) {
    return (
      <Card>
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant={quiz.status === "published" ? "success" : "warning"}>
                {quiz.status === "published" ? "Published" : "Draft"}
              </Badge>
              <Badge variant="neutral">
                {quiz.question_count} {quiz.question_count === 1 ? "question" : "questions"}
              </Badge>
            </div>
            <h3 className="mt-4 text-h2 text-night-900">{quiz.title}</h3>
            <p className="mt-3 text-body-md text-neutral-500">
              {quiz.description || "Review the quiz details, then start when you are ready."}
            </p>
          </div>

          <div className="rounded-3xl border border-neutral-200 bg-neutral-50 px-5 py-4">
            <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
              Quiz Snapshot
            </p>
            <div className="mt-4 space-y-2 text-body-md text-neutral-700">
              <p>{attemptsRemainingLabel}</p>
              <p>{quiz.time_limit_minutes ? `${quiz.time_limit_minutes} minute limit` : "Untimed"}</p>
              <p>Pass score: {quiz.pass_score}%</p>
            </div>
          </div>
        </div>

        {quiz.latest_attempt ? (
          <div className="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-4 text-body-md text-neutral-600">
            Most recent attempt:{" "}
            <span className="font-semibold text-night-900">
              {formatAttemptStatus(quiz.latest_attempt.status)}
            </span>
            {quiz.latest_attempt.score !== null ? ` • ${quiz.latest_attempt.score}%` : ""}
          </div>
        ) : null}

        {error ? <Alert tone="error" className="mt-6">{error}</Alert> : null}

        <div className="mt-6">
          <Button
            type="button"
            onClick={() => void handleStart()}
            disabled={isStarting || !canRetry}
          >
            {isStarting ? "Starting..." : quiz.latest_attempt ? "Start New Attempt" : "Start Quiz"}
          </Button>
        </div>
      </Card>
    );
  }

  if (attempt.status !== "in_progress") {
    return (
      <QuizResults
        attempt={attempt}
        canRetry={canRetry}
        isRetrying={isStarting}
        onRetry={canRetry ? () => void handleStart() : undefined}
      />
    );
  }

  return (
    <div className="space-y-5">
      <Card>
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant="info">
                Attempt In Progress
              </Badge>
              <Badge variant="neutral">
                {viewMode === "all" ? "All Questions" : "Single Question"}
              </Badge>
            </div>
            <h3 className="mt-4 text-h2 text-night-900">{attempt.quiz.title}</h3>
            <p className="mt-2 text-body-md text-neutral-500">
              Answer every question, then submit when you are ready.
            </p>
          </div>

          <div className="flex flex-col items-start gap-3 lg:items-end">
            <QuizTimer expiresAt={attempt.expires_at} onExpire={() => void handleSubmit()} />
            <div className="inline-flex rounded-full border border-neutral-200 bg-neutral-50 p-1">
              <button
                type="button"
                onClick={() => setViewMode("all")}
                className={`rounded-full px-3 py-1.5 text-body-sm font-semibold ${viewMode === "all" ? "bg-white text-night-900 shadow-sm" : "text-neutral-500"}`}
              >
                All Questions
              </button>
              <button
                type="button"
                onClick={() => setViewMode("single")}
                className={`rounded-full px-3 py-1.5 text-body-sm font-semibold ${viewMode === "single" ? "bg-white text-night-900 shadow-sm" : "text-neutral-500"}`}
              >
                One by One
              </button>
            </div>
          </div>
        </div>

        {error ? <Alert tone="error" className="mt-5">{error}</Alert> : null}
      </Card>

      <div className="space-y-4">
        {visibleQuestions.map((question, index) => {
          const questionIndex = attempt.questions.findIndex((item) => item.id === question.id);

          return (
            <QuestionCard
              key={question.id}
              question={question}
              index={questionIndex}
              value={answers[question.id] ?? {}}
              disabled={isSubmitting}
              onChange={(value) =>
                setAnswers((current) => ({
                  ...current,
                  [question.id]: value,
                }))
              }
            />
          );
        })}
      </div>

      <Card>
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="text-body-sm text-neutral-500">
            {viewMode === "single"
              ? `Question ${activeQuestionIndex + 1} of ${attempt.questions.length}`
              : `${attempt.questions.length} questions on this attempt`}
          </div>

          <div className="flex flex-wrap gap-3">
            {viewMode === "single" ? (
              <>
                <Button
                  type="button"
                  variant="secondary"
                  disabled={activeQuestionIndex === 0}
                  onClick={() => setActiveQuestionIndex((current) => current - 1)}
                >
                  Previous Question
                </Button>
                <Button
                  type="button"
                  variant="secondary"
                  disabled={activeQuestionIndex >= attempt.questions.length - 1}
                  onClick={() => setActiveQuestionIndex((current) => current + 1)}
                >
                  Next Question
                </Button>
              </>
            ) : null}
            <Button
              type="button"
              onClick={() => setConfirmOpen(true)}
              disabled={isSubmitting}
            >
              {isSubmitting ? "Submitting..." : "Submit Quiz"}
            </Button>
          </div>
        </div>
      </Card>

      <Modal
        open={confirmOpen}
        onClose={() => {
          if (!isSubmitting) {
            setConfirmOpen(false);
          }
        }}
        title="Submit Quiz"
        description="You can review your answers one more time before submitting."
        footer={(
          <>
            <Button type="button" variant="secondary" onClick={() => setConfirmOpen(false)}>
              Keep Reviewing
            </Button>
            <Button type="button" onClick={() => void handleSubmit()} disabled={isSubmitting}>
              {isSubmitting ? "Submitting..." : "Confirm Submission"}
            </Button>
          </>
        )}
      >
        <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-4 text-body-md text-neutral-600">
          This will submit all current answers for scoring.
        </div>
      </Modal>
    </div>
  );
}

function toAnswerMap(attempt: QuizAttempt): Record<number, QuizAttemptAnswerPayload> {
  return Object.fromEntries(
    attempt.questions.map((question) => [question.id, question.answer.answer_payload ?? {}]),
  );
}

function formatAttemptStatus(value: string): string {
  return value
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}
