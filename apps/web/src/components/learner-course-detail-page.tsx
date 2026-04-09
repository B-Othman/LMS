"use client";

import type {
  LearnerCourseDetail,
  LearnerLesson,
  LearnerModule,
  LessonContent,
  LessonProgress,
  LessonProgressStatus,
} from "@securecy/types";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import {
  useEffect,
  useMemo,
  useRef,
  useState,
  useTransition,
} from "react";

import {
  Badge,
  Button,
  Card,
  EmptyState,
  FileTextIcon,
  ProgressBar,
  ProtectedRoute,
  TypeIcon,
  VideoIcon,
} from "@securecy/ui";

import {
  buildCoursePlayerHref,
  completeLesson,
  fetchLessonContent,
  fetchLearnerCourseDetail,
  formatDurationMinutes,
  formatLongDate,
  recordLessonHeartbeat,
  startLesson,
} from "@/lib/learner-courses";

import { CompletionCheckmark } from "./completion-checkmark";
import { LessonSidebar } from "./lesson-sidebar";
import { LearnerStatusBadge } from "./learner-status-badge";
import { PDFViewer } from "./pdf-viewer";
import { VideoPlayer } from "./video-player";

interface LearnerCourseDetailPageProps {
  courseId: string;
}

export function LearnerCourseDetailPage({
  courseId,
}: LearnerCourseDetailPageProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [courseDetail, setCourseDetail] = useState<LearnerCourseDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [lessonContent, setLessonContent] = useState<LessonContent | null>(null);
  const [isLessonLoading, setIsLessonLoading] = useState(false);
  const [lessonError, setLessonError] = useState<string | null>(null);
  const [isCompleting, setIsCompleting] = useState(false);
  const [completionVisible, setCompletionVisible] = useState(false);
  const [isPending, startTransition] = useTransition();
  const heartbeatInFlightRef = useRef(false);

  const selectedLessonId = parseLessonId(searchParams.get("lesson"));

  useEffect(() => {
    let cancelled = false;

    setIsLoading(true);
    setLoadError(null);

    fetchLearnerCourseDetail(courseId)
      .then((detail) => {
        if (!cancelled) {
          setCourseDetail(detail);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setCourseDetail(null);
          setLoadError("The course detail could not be loaded.");
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
  }, [courseId]);

  const flattenedLessons = useMemo(
    () => flattenLessons(courseDetail?.course.modules ?? []),
    [courseDetail],
  );
  const currentLesson = useMemo(
    () =>
      selectedLessonId
        ? flattenedLessons.find((lesson) => lesson.id === selectedLessonId) ?? null
        : null,
    [flattenedLessons, selectedLessonId],
  );
  const currentLessonId = currentLesson?.id ?? null;
  const currentLessonIndex = useMemo(
    () => (currentLesson ? flattenedLessons.findIndex((lesson) => lesson.id === currentLesson.id) : -1),
    [currentLesson, flattenedLessons],
  );
  const previousLesson = currentLessonIndex > 0 ? flattenedLessons[currentLessonIndex - 1] : null;
  const nextLesson =
    currentLessonIndex >= 0 && currentLessonIndex < flattenedLessons.length - 1
      ? flattenedLessons[currentLessonIndex + 1]
      : null;
  const totalLessons =
    courseDetail?.enrollment.progress_summary?.total_lessons ?? flattenedLessons.length;
  const completedLessons =
    courseDetail?.enrollment.progress_summary?.completed_lessons ??
    courseDetail?.enrollment.completed_lessons_count ??
    0;
  const canTrackProgress = courseDetail?.enrollment.status === "active";

  useEffect(() => {
    if (!courseDetail || selectedLessonId === null) {
      return;
    }

    if (!currentLesson) {
      startTransition(() => {
        router.replace(`/courses/${courseId}`, { scroll: false });
      });
    }
  }, [courseDetail, courseId, currentLesson, router, selectedLessonId]);

  useEffect(() => {
    if (!currentLessonId) {
      setLessonContent(null);
      setLessonError(null);
      return;
    }

    let cancelled = false;

    setIsLessonLoading(true);
    setLessonError(null);

    const contentPromise = fetchLessonContent(currentLessonId);
    const startPromise = canTrackProgress ? startLesson(currentLessonId) : Promise.resolve(null);

    Promise.all([contentPromise, startPromise])
      .then(([content, detail]) => {
        if (cancelled) {
          return;
        }

        setLessonContent(content);

        if (detail) {
          setCourseDetail(detail);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setLessonContent(null);
          setLessonError("The lesson could not be loaded.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLessonLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [canTrackProgress, currentLessonId]);

  useEffect(() => {
    if (!currentLessonId || !canTrackProgress) {
      return;
    }

    const interval = window.setInterval(async () => {
      if (heartbeatInFlightRef.current) {
        return;
      }

      heartbeatInFlightRef.current = true;

      try {
        const progress = await recordLessonHeartbeat(currentLessonId, 30);
        setCourseDetail((current) => (current ? mergeLessonProgress(current, progress) : current));
      } catch {
        // Ignore intermittent heartbeat failures and keep the player usable.
      } finally {
        heartbeatInFlightRef.current = false;
      }
    }, 30000);

    return () => {
      window.clearInterval(interval);
    };
  }, [canTrackProgress, currentLessonId]);

  useEffect(() => {
    if (!completionVisible) {
      return;
    }

    const timeout = window.setTimeout(() => {
      setCompletionVisible(false);
    }, 1600);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [completionVisible]);

  async function handleCompleteLesson() {
    if (!currentLesson || !canTrackProgress) {
      return;
    }

    setIsCompleting(true);
    setLessonError(null);

    try {
      const detail = await completeLesson(currentLesson.id);
      setCourseDetail(detail);
      setCompletionVisible(true);
    } catch {
      setLessonError("This lesson could not be marked as complete.");
    } finally {
      setIsCompleting(false);
    }
  }

  function navigateToLesson(lessonId: number) {
    const href = buildCoursePlayerHref(Number(courseId), lessonId);

    startTransition(() => {
      router.push(href, { scroll: false });
    });
  }

  function navigateToOverview() {
    startTransition(() => {
      router.push(`/courses/${courseId}`, { scroll: false });
    });
  }

  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-[1440px] px-6 py-8">
        {isLoading ? (
          <div className="space-y-6">
            <div className="h-56 animate-pulse rounded-card border border-neutral-200 bg-white shadow-card" />
            <div className="h-72 animate-pulse rounded-card border border-neutral-200 bg-white shadow-card" />
          </div>
        ) : loadError ? (
          <div className="rounded-card border border-error-200 bg-error-50 px-4 py-3 text-body-md text-error-700">
            {loadError}
          </div>
        ) : courseDetail ? (
          <>
            {currentLesson ? (
              <div className="space-y-6">
                <div className="rounded-[28px] border border-primary-100 bg-white px-5 py-4 shadow-card">
                  <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                      <div className="flex flex-wrap items-center gap-3">
                        <Link href="/dashboard" className="text-body-sm font-semibold text-primary-700 underline">
                          Back to Dashboard
                        </Link>
                        <button
                          type="button"
                          onClick={navigateToOverview}
                          className="text-body-sm font-semibold text-neutral-600 underline"
                        >
                          Course Overview
                        </button>
                      </div>
                      <h1 className="mt-3 text-h2 text-night-900">{courseDetail.course.title}</h1>
                      <p className="mt-2 text-body-md text-neutral-500">{currentLesson.title}</p>
                    </div>

                    <div className="w-full lg:max-w-sm">
                      <ProgressBar
                        value={courseDetail.enrollment.progress_percentage}
                        label="Overall Progress"
                      />
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                  <LessonSidebar
                    courseTitle={courseDetail.course.title}
                    modules={courseDetail.course.modules}
                    currentLessonId={currentLesson.id}
                    onSelectLesson={navigateToLesson}
                  />

                  <div className="space-y-6">
                    <Card>
                      <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                          <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="neutral">{formatLessonType(currentLesson.type)}</Badge>
                            <LearnerStatusBadge status={courseDetail.enrollment.status} />
                          </div>
                          <h2 className="mt-4 text-h1 text-night-900">{currentLesson.title}</h2>
                          <p className="mt-3 text-body-md text-neutral-500">
                            {formatDurationMinutes(currentLesson.duration_minutes ?? 0)}
                          </p>
                        </div>

                        <div className="min-w-[180px]">
                          <ProgressBar
                            value={currentLesson.progress.progress_percentage}
                            label="Lesson Progress"
                          />
                        </div>
                      </div>
                    </Card>

                    {lessonError ? (
                      <div className="rounded-card border border-error-200 bg-error-50 px-4 py-3 text-body-md text-error-700">
                        {lessonError}
                      </div>
                    ) : null}

                    <Card className="overflow-hidden">
                      {isLessonLoading ? (
                        <div className="space-y-4">
                          <div className="h-72 animate-pulse rounded-3xl bg-neutral-100" />
                          <div className="h-6 w-2/3 animate-pulse rounded bg-neutral-100" />
                          <div className="h-24 animate-pulse rounded bg-neutral-100" />
                        </div>
                      ) : lessonContent ? (
                        <div className="space-y-6">
                          <LessonContentBody
                            lesson={currentLesson}
                            content={lessonContent}
                            onVideoEnded={() => {
                              if (currentLesson.progress.status !== "completed") {
                                void handleCompleteLesson();
                              }
                            }}
                          />

                          <div className="flex flex-col gap-4 border-t border-neutral-200 pt-6 md:flex-row md:items-center md:justify-between">
                            <div className="text-body-sm text-neutral-500">
                              {currentLesson.progress.status === "completed"
                                ? "This lesson has already been completed."
                                : canTrackProgress
                                  ? "Your progress is tracked automatically while you learn."
                                  : "This enrollment is no longer active, so progress cannot be updated."}
                            </div>

                            <div className="flex flex-wrap gap-3">
                              <Button
                                type="button"
                                variant="secondary"
                                disabled={!previousLesson || isPending}
                                onClick={() => previousLesson && navigateToLesson(previousLesson.id)}
                              >
                                Previous Lesson
                              </Button>
                              {currentLesson.progress.status === "completed" ? (
                                <Button
                                  type="button"
                                  disabled
                                  variant="success"
                                >
                                  Completed
                                </Button>
                              ) : (
                                <Button
                                  type="button"
                                  disabled={!canTrackProgress || isCompleting}
                                  onClick={() => void handleCompleteLesson()}
                                >
                                  {isCompleting ? "Saving..." : "Mark as Complete"}
                                </Button>
                              )}
                              <Button
                                type="button"
                                disabled={!nextLesson || isPending}
                                onClick={() => nextLesson && navigateToLesson(nextLesson.id)}
                              >
                                Next Lesson
                              </Button>
                            </div>
                          </div>
                        </div>
                      ) : (
                        <EmptyState
                          title="Lesson unavailable"
                          description="The lesson content is not available right now."
                        />
                      )}
                    </Card>
                  </div>
                </div>
              </div>
            ) : (
              <div className="space-y-8">
                <Link
                  href="/courses"
                  className="inline-flex items-center text-body-sm font-semibold text-primary-700 underline"
                >
                  Back to My Courses
                </Link>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.4fr)_340px]">
                  <Card className="overflow-hidden p-0">
                    <div className="relative h-64 overflow-hidden bg-primary-100">
                      {courseDetail.course.thumbnail_url ? (
                        <img
                          src={courseDetail.course.thumbnail_url}
                          alt={courseDetail.course.title}
                          className="h-full w-full object-cover"
                        />
                      ) : (
                        <div className="flex h-full w-full items-end bg-[radial-gradient(circle_at_top_left,_rgba(91,146,198,0.3),_rgba(245,249,252,0.9)_56%,_rgba(255,255,255,1))] p-6">
                          <p className="max-w-2xl text-h1 text-night-900">{courseDetail.course.title}</p>
                        </div>
                      )}
                    </div>

                    <div className="p-6">
                      <div className="flex flex-wrap items-center gap-3">
                        <LearnerStatusBadge status={courseDetail.enrollment.status} />
                        <Badge variant="neutral">
                          {totalLessons} {totalLessons === 1 ? "lesson" : "lessons"}
                        </Badge>
                      </div>

                      <h1 className="mt-4 text-h1 text-night-900">{courseDetail.course.title}</h1>
                      <p className="mt-3 max-w-3xl text-body-lg text-neutral-600">
                        {courseDetail.course.description ||
                          courseDetail.course.short_description ||
                          "Course overview coming soon."}
                      </p>

                      <div className="mt-6 flex flex-wrap gap-4 text-body-md text-neutral-500">
                        <span>
                          Instructor: {courseDetail.course.creator?.full_name ?? "Securecy Team"}
                        </span>
                        <span>Due: {formatLongDate(courseDetail.enrollment.due_at)}</span>
                      </div>

                      <div className="mt-8 flex flex-wrap gap-3">
                        <Button
                          type="button"
                          disabled={!courseDetail.enrollment.next_lesson_id || isPending}
                          onClick={() =>
                            courseDetail.enrollment.next_lesson_id &&
                            navigateToLesson(courseDetail.enrollment.next_lesson_id)
                          }
                        >
                          {primaryActionLabel(courseDetail)}
                        </Button>
                        <Link
                          href="/dashboard"
                          className="inline-flex items-center justify-center rounded-lg border border-neutral-300 bg-neutral-100 px-4 py-2 text-button text-neutral-700 transition-colors hover:bg-neutral-200 active:bg-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-500 focus-visible:ring-offset-2"
                        >
                          Back to Dashboard
                        </Link>
                      </div>
                    </div>
                  </Card>

                  <div className="space-y-4">
                    <Card>
                      <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
                        Progress Summary
                      </p>
                      <p className="mt-3 text-h3 text-night-900">
                        {completedLessons} of {totalLessons} lessons complete
                      </p>
                      <div className="mt-4">
                        <ProgressBar
                          value={courseDetail.enrollment.progress_percentage}
                          label="Overall Progress"
                        />
                      </div>
                    </Card>

                    <Card>
                      <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
                        Enrollment
                      </p>
                      <div className="mt-4 space-y-3 text-body-md text-neutral-600">
                        <div className="flex items-center justify-between gap-3">
                          <span>Status</span>
                          <LearnerStatusBadge status={courseDetail.enrollment.status} />
                        </div>
                        <div className="flex items-center justify-between gap-3">
                          <span>Enrolled On</span>
                          <span>{formatLongDate(courseDetail.enrollment.enrolled_at)}</span>
                        </div>
                        <div className="flex items-center justify-between gap-3">
                          <span>Due Date</span>
                          <span>{formatLongDate(courseDetail.enrollment.due_at)}</span>
                        </div>
                      </div>
                    </Card>
                  </div>
                </div>

                <Card>
                  <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                      <h2 className="text-h2 text-night-900">Course Outline</h2>
                      <p className="mt-2 text-body-md text-neutral-500">
                        Review the modules and jump directly into the next lesson when you are ready.
                      </p>
                    </div>
                  </div>

                  <div className="mt-6">
                    <CourseOutlineAccordion
                      modules={courseDetail.course.modules}
                      onSelectLesson={(lessonId) => navigateToLesson(lessonId)}
                    />
                  </div>
                </Card>
              </div>
            )}
          </>
        ) : (
          <EmptyState
            title="Course not found"
            description="This course is not available for your account right now."
          />
        )}
      </div>

      <CompletionCheckmark visible={completionVisible} />
    </ProtectedRoute>
  );
}

function CourseOutlineAccordion({
  modules,
  onSelectLesson,
}: {
  modules: LearnerModule[];
  onSelectLesson: (lessonId: number) => void;
}) {
  const [openModules, setOpenModules] = useState<Record<number, boolean>>(() =>
    Object.fromEntries(modules.map((module, index) => [module.id, index === 0])),
  );

  useEffect(() => {
    setOpenModules((current) => {
      const next = { ...current };

      for (const [index, module] of modules.entries()) {
        if (!(module.id in next)) {
          next[module.id] = index === 0;
        }
      }

      return next;
    });
  }, [modules]);

  if (modules.length === 0) {
    return (
      <EmptyState
        title="No modules yet"
        description="This course has not published its learning modules yet."
      />
    );
  }

  return (
    <div className="space-y-4">
      {modules.map((module, index) => {
        const isOpen = openModules[module.id] ?? index === 0;

        return (
          <div key={module.id} className="rounded-3xl border border-neutral-200 bg-neutral-50">
            <button
              type="button"
              onClick={() =>
                setOpenModules((current) => ({
                  ...current,
                  [module.id]: !isOpen,
                }))
              }
              className="flex w-full items-start justify-between gap-3 px-5 py-4 text-left"
            >
              <div>
                <p className="text-overline uppercase tracking-[0.2em] text-primary-700">Module {index + 1}</p>
                <h3 className="mt-2 text-h3 text-night-900">{module.title}</h3>
                {module.description ? (
                  <p className="mt-2 text-body-md text-neutral-500">{module.description}</p>
                ) : null}
              </div>
              <Badge variant="neutral">
                {module.lesson_count} {module.lesson_count === 1 ? "lesson" : "lessons"}
              </Badge>
            </button>

            {isOpen ? (
              <div className="space-y-2 border-t border-neutral-200 px-4 py-4">
                {module.lessons.map((lesson) => (
                  <button
                    key={lesson.id}
                    type="button"
                    onClick={() => onSelectLesson(lesson.id)}
                    className="flex w-full items-center justify-between gap-4 rounded-2xl bg-white px-4 py-3 text-left transition-colors hover:border-primary-200 hover:bg-primary-50"
                  >
                    <div className="flex min-w-0 items-center gap-3">
                      <LessonTypeIcon type={lesson.type} />
                      <div className="min-w-0">
                        <p className="truncate text-body-md font-semibold text-night-900">{lesson.title}</p>
                        <p className="mt-1 text-body-sm text-neutral-500">
                          {formatLessonType(lesson.type)}
                        </p>
                      </div>
                    </div>
                    <div className="flex shrink-0 items-center gap-3">
                      <Badge variant={progressVariant(lesson.progress.status)}>
                        {progressLabel(lesson.progress.status)}
                      </Badge>
                      <span className="text-body-sm text-neutral-500">
                        {formatDurationMinutes(lesson.duration_minutes ?? 0)}
                      </span>
                    </div>
                  </button>
                ))}
              </div>
            ) : null}
          </div>
        );
      })}
    </div>
  );
}

function LessonContentBody({
  lesson,
  content,
  onVideoEnded,
}: {
  lesson: LearnerLesson;
  content: LessonContent;
  onVideoEnded: () => void;
}) {
  if (lesson.type === "video" && content.content_url) {
    return (
      <div className="space-y-4">
        <VideoPlayer
          src={content.content_url}
          title={content.title}
          poster={content.media?.thumbnail_url}
          onEnded={onVideoEnded}
        />
        <p className="text-body-sm text-neutral-500">
          The lesson will be marked complete automatically when the video finishes.
        </p>
      </div>
    );
  }

  if (lesson.type === "document" && content.content_url) {
    return (
      <PDFViewer
        src={content.content_url}
        title={content.title}
        downloadUrl={content.download_url}
      />
    );
  }

  return (
    <div className="rounded-[28px] border border-neutral-200 bg-neutral-50 px-6 py-6 text-body-md leading-7 text-night-900">
      <div
        dangerouslySetInnerHTML={{
          __html: content.content_html ?? "<p>This lesson does not have any content yet.</p>",
        }}
      />
    </div>
  );
}

function LessonTypeIcon({ type }: { type: string }) {
  if (type === "video") {
    return <VideoIcon className="h-5 w-5 text-primary-700" />;
  }

  if (type === "document") {
    return <FileTextIcon className="h-5 w-5 text-primary-700" />;
  }

  return <TypeIcon className="h-5 w-5 text-primary-700" />;
}

function flattenLessons(modules: LearnerModule[]): LearnerLesson[] {
  return modules.flatMap((module) => module.lessons);
}

function mergeLessonProgress(
  detail: LearnerCourseDetail,
  progress: LessonProgress,
): LearnerCourseDetail {
  return {
    ...detail,
    enrollment: {
      ...detail.enrollment,
      last_accessed_lesson_id: progress.lesson_id,
    },
    course: {
      ...detail.course,
      modules: detail.course.modules.map((module) => ({
        ...module,
        lessons: module.lessons.map((lesson) =>
          lesson.id === progress.lesson_id
            ? {
                ...lesson,
                progress: {
                  ...lesson.progress,
                  ...progress,
                },
              }
            : lesson,
        ),
      })),
    },
  };
}

function parseLessonId(value: string | null): number | null {
  if (!value) {
    return null;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : null;
}

function primaryActionLabel(detail: LearnerCourseDetail): string {
  if (detail.enrollment.status === "completed") {
    return "Review Course";
  }

  return detail.enrollment.progress_percentage > 0 ? "Continue Learning" : "Start Course";
}

function formatLessonType(value: string): string {
  return value
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

function progressLabel(value: LessonProgressStatus): string {
  const labels: Record<LessonProgressStatus, string> = {
    not_started: "Not Started",
    in_progress: "In Progress",
    completed: "Completed",
  };

  return labels[value];
}

function progressVariant(
  value: LessonProgressStatus,
): "success" | "info" | "neutral" {
  if (value === "completed") {
    return "success";
  }

  if (value === "in_progress") {
    return "info";
  }

  return "neutral";
}
