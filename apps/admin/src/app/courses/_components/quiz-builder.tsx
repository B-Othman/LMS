"use client";

import type {
  CreateQuizQuestionPayload,
  Lesson,
  Quiz,
  QuizQuestion,
  QuizQuestionOptionInput,
  QuizQuestionType,
  QuizStatus,
  QuizSummary,
} from "@securecy/types";
import { useEffect, useState } from "react";

import {
  Alert,
  Badge,
  Button,
  Card,
  ChevronDownIcon,
  ChevronUpIcon,
  Input,
  Label,
  Modal,
  PencilIcon,
  PlusIcon,
  Select,
  TrashIcon,
  useToast,
} from "@securecy/ui";

import {
  addQuizQuestion,
  createQuiz,
  deleteQuizQuestion,
  fetchQuiz,
  reorderQuizQuestions,
  updateQuiz,
  updateQuizQuestion,
} from "@/lib/quizzes";

const questionTypeOptions = [
  { label: "Multiple Choice", value: "multiple_choice" },
  { label: "Multi Select", value: "multi_select" },
  { label: "True / False", value: "true_false" },
  { label: "Short Answer", value: "short_answer" },
];

const quizStatusOptions = [
  { label: "Draft", value: "draft" },
  { label: "Published", value: "published" },
];

interface QuizBuilderProps {
  courseId: number;
  lesson: Lesson;
  onQuizSummaryChange: (quiz: QuizSummary) => void;
}

interface QuizFormState {
  title: string;
  description: string;
  pass_score: string;
  time_limit_minutes: string;
  attempts_allowed: string;
  shuffle_questions: boolean;
  show_results_to_learner: boolean;
  status: QuizStatus;
}

interface QuestionDraftState {
  id?: number;
  question_type: QuizQuestionType;
  prompt: string;
  explanation: string;
  points: string;
  options: QuizQuestionOptionInput[];
}

export function QuizBuilder({
  courseId,
  lesson,
  onQuizSummaryChange,
}: QuizBuilderProps) {
  const { showToast } = useToast();
  const [quiz, setQuiz] = useState<Quiz | null>(null);
  const [form, setForm] = useState<QuizFormState>(() => createDefaultQuizForm(lesson.title));
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [builderError, setBuilderError] = useState<string | null>(null);
  const [questionModalOpen, setQuestionModalOpen] = useState(false);
  const [previewOpen, setPreviewOpen] = useState(false);
  const [questionDraft, setQuestionDraft] = useState<QuestionDraftState>(() =>
    createDefaultQuestionDraft(),
  );
  const [isQuestionSaving, setIsQuestionSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;

    if (!lesson.quiz?.id) {
      setQuiz(null);
      setForm(createDefaultQuizForm(lesson.title));
      setBuilderError(null);
      return;
    }

    setIsLoading(true);
    setBuilderError(null);

    fetchQuiz(lesson.quiz.id)
      .then((response) => {
        if (cancelled) {
          return;
        }

        setQuiz(response);
        setForm(createQuizFormFromQuiz(response));
      })
      .catch(() => {
        if (!cancelled) {
          setQuiz(null);
          setBuilderError("The quiz builder could not load this lesson quiz.");
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
  }, [lesson.id, lesson.quiz?.id, lesson.title]);

  async function handleSaveQuiz() {
    setIsSaving(true);
    setBuilderError(null);

    try {
      const payload = {
        title: form.title.trim() || lesson.title,
        description: form.description.trim() || null,
        pass_score: Number(form.pass_score || 0),
        time_limit_minutes: form.time_limit_minutes ? Number(form.time_limit_minutes) : null,
        attempts_allowed: Number(form.attempts_allowed || 0),
        shuffle_questions: form.shuffle_questions,
        show_results_to_learner: form.show_results_to_learner,
        status: form.status,
      } as const;

      const savedQuiz = quiz
        ? await updateQuiz(quiz.id, payload)
        : await createQuiz({
            course_id: courseId,
            lesson_id: lesson.id,
            ...payload,
          });

      setQuiz(savedQuiz);
      setForm(createQuizFormFromQuiz(savedQuiz));
      onQuizSummaryChange(toQuizSummary(savedQuiz));
      showToast({
        tone: "success",
        title: quiz ? "Quiz updated" : "Quiz created",
        message: quiz
          ? "Quiz settings were saved."
          : "The lesson quiz is ready for questions.",
      });
    } catch {
      setBuilderError("The quiz could not be saved.");
    } finally {
      setIsSaving(false);
    }
  }

  async function handleSaveQuestion() {
    if (!quiz) {
      return;
    }

    setIsQuestionSaving(true);

    try {
      const payload: CreateQuizQuestionPayload = {
        question_type: questionDraft.question_type,
        prompt: questionDraft.prompt.trim(),
        explanation: questionDraft.explanation.trim() || null,
        points: Number(questionDraft.points || 1),
        options: isChoiceQuestion(questionDraft.question_type)
          ? questionDraft.options.map((option, index) => ({
              id: option.id,
              label: option.label.trim(),
              is_correct: option.is_correct,
              sort_order: index + 1,
            }))
          : [],
      };

      const updatedQuiz = questionDraft.id
        ? await updateQuizQuestion(questionDraft.id, payload)
        : await addQuizQuestion(quiz.id, payload);

      setQuiz(updatedQuiz);
      onQuizSummaryChange(toQuizSummary(updatedQuiz));
      setQuestionModalOpen(false);
      setQuestionDraft(createDefaultQuestionDraft());
      showToast({
        tone: "success",
        title: questionDraft.id ? "Question updated" : "Question added",
        message: "The quiz question was saved.",
      });
    } catch {
      showToast({
        tone: "error",
        title: "Question failed",
        message: "The question could not be saved.",
      });
    } finally {
      setIsQuestionSaving(false);
    }
  }

  async function handleDeleteQuestion(question: QuizQuestion) {
    if (!window.confirm("Delete this question?")) {
      return;
    }

    try {
      const updatedQuiz = await deleteQuizQuestion(question.id);
      setQuiz(updatedQuiz);
      onQuizSummaryChange(toQuizSummary(updatedQuiz));
      showToast({
        tone: "success",
        title: "Question deleted",
        message: "The question was removed from this quiz.",
      });
    } catch {
      showToast({
        tone: "error",
        title: "Delete failed",
        message: "The question could not be deleted.",
      });
    }
  }

  async function handleMoveQuestion(index: number, direction: -1 | 1) {
    if (!quiz) {
      return;
    }

    const targetIndex = index + direction;

    if (targetIndex < 0 || targetIndex >= quiz.questions.length) {
      return;
    }

    const reordered = [...quiz.questions];
    const [moved] = reordered.splice(index, 1);
    reordered.splice(targetIndex, 0, moved);

    try {
      const updatedQuiz = await reorderQuizQuestions(
        quiz.id,
        reordered.map((question, currentIndex) => ({
          id: question.id,
          sort_order: currentIndex + 1,
        })),
      );

      setQuiz(updatedQuiz);
      showToast({
        tone: "success",
        title: "Order updated",
        message: "Quiz questions were reordered.",
      });
    } catch {
      showToast({
        tone: "error",
        title: "Reorder failed",
        message: "The question order could not be updated.",
      });
    }
  }

  function openNewQuestion() {
    setQuestionDraft(createDefaultQuestionDraft());
    setQuestionModalOpen(true);
  }

  function openEditQuestion(question: QuizQuestion) {
    setQuestionDraft({
      id: question.id,
      question_type: question.question_type,
      prompt: question.prompt,
      explanation: question.explanation ?? "",
      points: String(question.points),
      options: question.options.map((option, index) => ({
        id: option.id,
        label: option.label,
        is_correct: Boolean(option.is_correct),
        sort_order: index + 1,
      })),
    });
    setQuestionModalOpen(true);
  }

  function updateQuestionType(nextType: QuizQuestionType) {
    setQuestionDraft((current) => ({
      ...current,
      question_type: nextType,
      options: buildOptionsForQuestionType(nextType, current.options),
    }));
  }

  return (
    <Card className="mt-6">
      <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
          <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
            Quiz Builder
          </p>
          <h3 className="mt-2 text-h3 text-night-900">{lesson.title}</h3>
          <p className="mt-2 text-body-md text-neutral-500">
            Configure the quiz, then add and arrange questions for this lesson.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {quiz ? <Badge variant={quiz.status === "published" ? "success" : "warning"}>{quiz.status}</Badge> : null}
          {quiz ? (
            <Badge variant="info">
              {quiz.question_count} {quiz.question_count === 1 ? "question" : "questions"}
            </Badge>
          ) : null}
        </div>
      </div>

      {builderError ? <Alert tone="error" className="mt-5">{builderError}</Alert> : null}

      {isLoading ? (
        <div className="mt-6 space-y-4">
          <div className="h-10 animate-pulse rounded bg-neutral-100" />
          <div className="h-40 animate-pulse rounded bg-neutral-100" />
        </div>
      ) : (
        <>
          <div className="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor={`quiz-title-${lesson.id}`}>Quiz Title</Label>
              <Input
                id={`quiz-title-${lesson.id}`}
                value={form.title}
                onChange={(event) => setForm((current) => ({ ...current, title: event.target.value }))}
                placeholder="Lesson quiz title"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor={`quiz-status-${lesson.id}`}>Status</Label>
              <Select
                id={`quiz-status-${lesson.id}`}
                value={form.status}
                options={quizStatusOptions}
                onChange={(event) =>
                  setForm((current) => ({ ...current, status: event.target.value as QuizStatus }))
                }
              />
            </div>

            <div className="space-y-2 xl:col-span-2">
              <Label htmlFor={`quiz-description-${lesson.id}`}>Description</Label>
              <textarea
                id={`quiz-description-${lesson.id}`}
                rows={4}
                value={form.description}
                onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))}
                className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-body-md text-night-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                placeholder="What should learners know before they start this quiz?"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor={`quiz-pass-score-${lesson.id}`}>Pass Score (%)</Label>
              <Input
                id={`quiz-pass-score-${lesson.id}`}
                type="number"
                min={0}
                max={100}
                value={form.pass_score}
                onChange={(event) => setForm((current) => ({ ...current, pass_score: event.target.value }))}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor={`quiz-time-limit-${lesson.id}`}>Time Limit (minutes)</Label>
              <Input
                id={`quiz-time-limit-${lesson.id}`}
                type="number"
                min={1}
                value={form.time_limit_minutes}
                onChange={(event) =>
                  setForm((current) => ({ ...current, time_limit_minutes: event.target.value }))
                }
                placeholder="Leave blank for untimed"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor={`quiz-attempts-${lesson.id}`}>Attempts Allowed</Label>
              <Input
                id={`quiz-attempts-${lesson.id}`}
                type="number"
                min={0}
                value={form.attempts_allowed}
                onChange={(event) =>
                  setForm((current) => ({ ...current, attempts_allowed: event.target.value }))
                }
                placeholder="0 = unlimited"
              />
            </div>

            <div className="space-y-3">
              <Label>Delivery</Label>
              <label className="flex items-center gap-3 rounded-xl border border-neutral-200 px-3 py-2">
                <input
                  type="checkbox"
                  checked={form.shuffle_questions}
                  onChange={(event) =>
                    setForm((current) => ({ ...current, shuffle_questions: event.target.checked }))
                  }
                />
                <span className="text-body-md text-night-900">Shuffle questions for learners</span>
              </label>
              <label className="flex items-center gap-3 rounded-xl border border-neutral-200 px-3 py-2">
                <input
                  type="checkbox"
                  checked={form.show_results_to_learner}
                  onChange={(event) =>
                    setForm((current) => ({
                      ...current,
                      show_results_to_learner: event.target.checked,
                    }))
                  }
                />
                <span className="text-body-md text-night-900">Show results after submission</span>
              </label>
            </div>
          </div>

          <div className="mt-6 flex flex-wrap gap-3">
            <Button type="button" onClick={() => void handleSaveQuiz()} disabled={isSaving}>
              {isSaving ? "Saving..." : quiz ? "Save Quiz Settings" : "Create Quiz"}
            </Button>
            {quiz ? (
              <Button type="button" variant="secondary" onClick={() => setPreviewOpen(true)}>
                Preview
              </Button>
            ) : null}
          </div>

          {quiz ? (
            <div className="mt-8 rounded-3xl border border-neutral-200 bg-neutral-50 p-5">
              <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <h4 className="text-h4 text-night-900">Questions</h4>
                  <p className="mt-2 text-body-md text-neutral-500">
                    Add, edit, and reorder questions for this quiz lesson.
                  </p>
                </div>
                <Button type="button" onClick={openNewQuestion}>
                  <PlusIcon className="mr-2 h-4 w-4" />
                  Add Question
                </Button>
              </div>

              <div className="mt-5 space-y-3">
                {quiz.questions.length === 0 ? (
                  <div className="rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-8 text-center text-body-md text-neutral-500">
                    No questions yet. Add the first question to make this quiz ready for learners.
                  </div>
                ) : (
                  quiz.questions.map((question, index) => (
                    <div key={question.id} className="rounded-2xl border border-neutral-200 bg-white p-4">
                      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0">
                          <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="info">{formatQuestionType(question.question_type)}</Badge>
                            <Badge variant="neutral">{question.points} pts</Badge>
                          </div>
                          <p className="mt-3 text-body-lg font-semibold text-night-900">
                            {stripHtml(question.prompt) || "Untitled question"}
                          </p>
                          {question.explanation ? (
                            <p className="mt-2 text-body-sm text-neutral-500">
                              Explanation ready for learner review.
                            </p>
                          ) : null}
                        </div>

                        <div className="flex shrink-0 flex-wrap gap-2">
                          <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            disabled={index === 0}
                            onClick={() => void handleMoveQuestion(index, -1)}
                          >
                            <ChevronUpIcon className="h-4 w-4" />
                          </Button>
                          <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            disabled={index === quiz.questions.length - 1}
                            onClick={() => void handleMoveQuestion(index, 1)}
                          >
                            <ChevronDownIcon className="h-4 w-4" />
                          </Button>
                          <Button type="button" size="sm" variant="secondary" onClick={() => openEditQuestion(question)}>
                            <PencilIcon className="mr-2 h-4 w-4" />
                            Edit
                          </Button>
                          <Button type="button" size="sm" variant="danger" onClick={() => void handleDeleteQuestion(question)}>
                            <TrashIcon className="mr-2 h-4 w-4" />
                            Delete
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          ) : null}
        </>
      )}

      <Modal
        open={questionModalOpen}
        onClose={() => {
          if (!isQuestionSaving) {
            setQuestionModalOpen(false);
            setQuestionDraft(createDefaultQuestionDraft());
          }
        }}
        title={questionDraft.id ? "Edit Question" : "Add Question"}
        description="Prompts accept HTML so you can style or emphasize parts of the question."
        size="lg"
        footer={(
          <>
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                setQuestionModalOpen(false);
                setQuestionDraft(createDefaultQuestionDraft());
              }}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => void handleSaveQuestion()}
              disabled={isQuestionSaving || !questionDraft.prompt.trim()}
            >
              {isQuestionSaving ? "Saving..." : questionDraft.id ? "Save Question" : "Add Question"}
            </Button>
          </>
        )}
      >
        <div className="space-y-5">
          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div className="space-y-2">
              <Label>Question Type</Label>
              <Select
                value={questionDraft.question_type}
                options={questionTypeOptions}
                onChange={(event) => updateQuestionType(event.target.value as QuizQuestionType)}
              />
            </div>
            <div className="space-y-2">
              <Label>Points</Label>
              <Input
                type="number"
                min={1}
                value={questionDraft.points}
                onChange={(event) =>
                  setQuestionDraft((current) => ({ ...current, points: event.target.value }))
                }
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label>Prompt</Label>
            <textarea
              rows={5}
              value={questionDraft.prompt}
              onChange={(event) =>
                setQuestionDraft((current) => ({ ...current, prompt: event.target.value }))
              }
              className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-body-md text-night-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            />
          </div>

          <div className="space-y-2">
            <Label>Explanation</Label>
            <textarea
              rows={3}
              value={questionDraft.explanation}
              onChange={(event) =>
                setQuestionDraft((current) => ({
                  ...current,
                  explanation: event.target.value,
                }))
              }
              className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-body-md text-night-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              placeholder="Shown after submission when learner results are enabled."
            />
          </div>

          {isChoiceQuestion(questionDraft.question_type) ? (
            <div className="space-y-3">
              <div className="flex items-center justify-between gap-3">
                <Label>Options</Label>
                {questionDraft.question_type !== "true_false" ? (
                  <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    onClick={() =>
                      setQuestionDraft((current) => ({
                        ...current,
                        options: [
                          ...current.options,
                          { label: "", is_correct: false, sort_order: current.options.length + 1 },
                        ],
                      }))
                    }
                  >
                    <PlusIcon className="mr-2 h-4 w-4" />
                    Add Option
                  </Button>
                ) : null}
              </div>

              <div className="space-y-3">
                {questionDraft.options.map((option, index) => (
                  <div key={`${questionDraft.id ?? "new"}-${index}`} className="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 px-3 py-3">
                    <input
                      type={questionDraft.question_type === "multi_select" ? "checkbox" : "radio"}
                      checked={option.is_correct}
                      onChange={() => {
                        setQuestionDraft((current) => ({
                          ...current,
                          options: current.options.map((item, itemIndex) => ({
                            ...item,
                            is_correct: questionDraft.question_type === "multi_select"
                              ? itemIndex === index
                                ? !item.is_correct
                                : item.is_correct
                              : itemIndex === index,
                          })),
                        }));
                      }}
                    />
                    <Input
                      value={option.label}
                      disabled={questionDraft.question_type === "true_false"}
                      onChange={(event) =>
                        setQuestionDraft((current) => ({
                          ...current,
                          options: current.options.map((item, itemIndex) =>
                            itemIndex === index
                              ? { ...item, label: event.target.value }
                              : item,
                          ),
                        }))
                      }
                      placeholder={`Option ${index + 1}`}
                    />
                    {questionDraft.question_type !== "true_false" ? (
                      <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        disabled={questionDraft.options.length <= 2}
                        onClick={() =>
                          setQuestionDraft((current) => ({
                            ...current,
                            options: current.options.filter((_, itemIndex) => itemIndex !== index),
                          }))
                        }
                      >
                        <TrashIcon className="h-4 w-4" />
                      </Button>
                    ) : (
                      <span className="text-body-sm font-medium text-neutral-400">Fixed</span>
                    )}
                  </div>
                ))}
              </div>
            </div>
          ) : null}
        </div>
      </Modal>

      <Modal
        open={previewOpen}
        onClose={() => setPreviewOpen(false)}
        title={quiz?.title ?? "Quiz Preview"}
        description="This preview shows the quiz structure learners will see before any scoring logic is applied."
        size="xl"
        footer={(
          <Button type="button" variant="secondary" onClick={() => setPreviewOpen(false)}>
            Close
          </Button>
        )}
      >
        <div className="space-y-4">
          {quiz?.questions.length ? (
            quiz.questions.map((question, index) => (
              <div key={question.id} className="rounded-2xl border border-neutral-200 bg-neutral-50 p-4">
                <div className="flex flex-wrap items-center gap-2">
                  <Badge variant="info">{formatQuestionType(question.question_type)}</Badge>
                  <Badge variant="neutral">{question.points} pts</Badge>
                </div>
                <p className="mt-3 text-body-lg font-semibold text-night-900">
                  {index + 1}. {stripHtml(question.prompt)}
                </p>
                {question.options.length ? (
                  <ul className="mt-4 space-y-2 text-body-md text-neutral-700">
                    {question.options.map((option) => (
                      <li key={option.id} className="rounded-xl bg-white px-3 py-2">
                        {option.label}
                      </li>
                    ))}
                  </ul>
                ) : (
                  <p className="mt-4 rounded-xl bg-white px-3 py-3 text-body-md text-neutral-500">
                    Learners will answer this question with free text.
                  </p>
                )}
              </div>
            ))
          ) : (
            <p className="rounded-2xl border border-dashed border-neutral-300 px-5 py-8 text-center text-body-md text-neutral-500">
              Add at least one question to preview this quiz.
            </p>
          )}
        </div>
      </Modal>
    </Card>
  );
}

function createDefaultQuizForm(lessonTitle: string): QuizFormState {
  return {
    title: `${lessonTitle} Quiz`,
    description: "",
    pass_score: "80",
    time_limit_minutes: "",
    attempts_allowed: "0",
    shuffle_questions: false,
    show_results_to_learner: true,
    status: "draft",
  };
}

function createQuizFormFromQuiz(quiz: Quiz): QuizFormState {
  return {
    title: quiz.title,
    description: quiz.description ?? "",
    pass_score: String(quiz.pass_score),
    time_limit_minutes: quiz.time_limit_minutes ? String(quiz.time_limit_minutes) : "",
    attempts_allowed: String(quiz.attempts_allowed),
    shuffle_questions: quiz.shuffle_questions,
    show_results_to_learner: quiz.show_results_to_learner,
    status: quiz.status,
  };
}

function createDefaultQuestionDraft(
  type: QuizQuestionType = "multiple_choice",
): QuestionDraftState {
  return {
    question_type: type,
    prompt: "",
    explanation: "",
    points: "1",
    options: buildOptionsForQuestionType(type, []),
  };
}

function buildOptionsForQuestionType(
  type: QuizQuestionType,
  currentOptions: QuizQuestionOptionInput[],
): QuizQuestionOptionInput[] {
  if (type === "short_answer") {
    return [];
  }

  if (type === "true_false") {
    const trueOption = currentOptions[0];
    const falseOption = currentOptions[1];

    return [
      { id: trueOption?.id, label: "True", is_correct: trueOption?.is_correct ?? false },
      { id: falseOption?.id, label: "False", is_correct: falseOption?.is_correct ?? true },
    ];
  }

  if (currentOptions.length >= 2) {
    return currentOptions;
  }

  return [
    currentOptions[0] ?? { label: "", is_correct: true },
    currentOptions[1] ?? { label: "", is_correct: false },
  ];
}

function isChoiceQuestion(type: QuizQuestionType): boolean {
  return type !== "short_answer";
}

function toQuizSummary(quiz: Quiz): QuizSummary {
  return {
    id: quiz.id,
    course_id: quiz.course_id,
    lesson_id: quiz.lesson_id,
    title: quiz.title,
    description: quiz.description,
    pass_score: quiz.pass_score,
    time_limit_minutes: quiz.time_limit_minutes,
    attempts_allowed: quiz.attempts_allowed,
    shuffle_questions: quiz.shuffle_questions,
    show_results_to_learner: quiz.show_results_to_learner,
    status: quiz.status,
    question_count: quiz.question_count,
    attempts_used: quiz.attempts_used,
    attempts_remaining: quiz.attempts_remaining,
    latest_attempt: quiz.latest_attempt,
    created_at: quiz.created_at,
    updated_at: quiz.updated_at,
  };
}

function formatQuestionType(type: QuizQuestionType): string {
  return type
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

function stripHtml(value: string): string {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}
