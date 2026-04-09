"use client";

import type {
  BatchEnrollmentResult,
  Course,
  Enrollment,
  EnrollmentListFilters,
  EnrollmentStatus,
  User,
} from "@securecy/types";
import { useDeferredValue, useEffect, useMemo, useState, useTransition } from "react";

import {
  Badge,
  Button,
  DataTable,
  Input,
  Modal,
  MultiSelect,
  Pagination,
  ProgressBar,
  ProtectedRoute,
  Select,
  useAuth,
  useToast,
  type DataTableColumn,
} from "@securecy/ui";

import { api } from "@/lib/api";

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const emptyMeta: PaginationMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
};

const statusOptions = [
  { label: "All statuses", value: "" },
  { label: "Active", value: "active" },
  { label: "Completed", value: "completed" },
  { label: "Dropped", value: "dropped" },
  { label: "Expired", value: "expired" },
];

export function EnrollmentsPageClient() {
  const { hasPermission } = useAuth();
  const { showToast } = useToast();
  const [rows, setRows] = useState<Enrollment[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(emptyMeta);
  const [courses, setCourses] = useState<Course[]>([]);
  const [learnerOptions, setLearnerOptions] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [refreshTick, setRefreshTick] = useState(0);
  const [dropTarget, setDropTarget] = useState<Enrollment | null>(null);
  const [enrollModalOpen, setEnrollModalOpen] = useState(false);
  const [search, setSearch] = useState("");
  const deferredSearch = useDeferredValue(search);
  const [status, setStatus] = useState<EnrollmentStatus | "">("");
  const [courseId, setCourseId] = useState("");
  const [page, setPage] = useState(1);
  const [sortBy, setSortBy] = useState<NonNullable<EnrollmentListFilters["sort_by"]>>("enrolled_at");
  const [sortDir, setSortDir] = useState<NonNullable<EnrollmentListFilters["sort_dir"]>>("desc");
  const [courseSearch, setCourseSearch] = useState("");
  const [selectedCourseId, setSelectedCourseId] = useState("");
  const [learnerSearch, setLearnerSearch] = useState("");
  const deferredLearnerSearch = useDeferredValue(learnerSearch);
  const [selectedUserIds, setSelectedUserIds] = useState<string[]>([]);
  const [dueAt, setDueAt] = useState("");
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    let cancelled = false;

    api
      .paginated<Course>("/courses", {
        params: {
          per_page: 100,
          sort_by: "title",
          sort_dir: "asc",
        },
      })
      .then((response) => {
        if (!cancelled) {
          setCourses(response.data ?? []);
        }
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!enrollModalOpen || !hasPermission("enrollments.create")) {
      return;
    }

    let cancelled = false;

    api
      .paginated<User>("/users", {
        params: {
          role: "learner",
          search: deferredLearnerSearch || undefined,
          sort_by: "name",
          sort_dir: "asc",
          per_page: 100,
        },
      })
      .then((response) => {
        if (!cancelled) {
          setLearnerOptions(response.data ?? []);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setLearnerOptions([]);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [deferredLearnerSearch, enrollModalOpen, hasPermission]);

  useEffect(() => {
    let cancelled = false;
    setIsLoading(true);

    api
      .paginated<Enrollment>("/enrollments", {
        params: {
          search: deferredSearch || undefined,
          status: status || undefined,
          course_id: courseId || undefined,
          sort_by: sortBy,
          sort_dir: sortDir,
          per_page: emptyMeta.per_page,
          page,
        },
      })
      .then((response) => {
        if (cancelled) {
          return;
        }

        setRows(response.data ?? []);
        setMeta((response.meta as PaginationMeta | undefined) ?? emptyMeta);
      })
      .catch(() => {
        if (!cancelled) {
          setRows([]);
          setMeta(emptyMeta);
          showToast({
            tone: "error",
            title: "Enrollments unavailable",
            message: "The enrollment list could not be loaded.",
          });
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
  }, [courseId, deferredSearch, page, refreshTick, showToast, sortBy, sortDir, status]);

  const courseFilterOptions = useMemo(
    () => [
      { label: "All courses", value: "" },
      ...courses.map((course) => ({
        label: course.title,
        value: String(course.id),
      })),
    ],
    [courses],
  );

  const publishedCourseOptions = useMemo(
    () =>
      courses
        .filter((course) => course.status === "published")
        .filter((course) => course.title.toLowerCase().includes(courseSearch.trim().toLowerCase()))
        .map((course) => ({
          label: course.title,
          value: String(course.id),
        })),
    [courseSearch, courses],
  );

  const learnerMultiSelectOptions = useMemo(
    () =>
      learnerOptions.map((learner) => ({
        label: learner.full_name ?? `${learner.first_name} ${learner.last_name}`,
        value: String(learner.id),
        description: learner.email,
      })),
    [learnerOptions],
  );

  async function handleSubmitEnrollment() {
    if (!selectedCourseId || selectedUserIds.length === 0) {
      showToast({
        tone: "error",
        title: "Missing details",
        message: "Select a course and at least one learner.",
      });
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await api.post<BatchEnrollmentResult>("/enrollments", {
        course_id: Number(selectedCourseId),
        user_ids: selectedUserIds.map(Number),
        due_at: dueAt || undefined,
      });

      const result = response.data;
      const failureCount = result?.failure_count ?? 0;
      const successCount = result?.success_count ?? 0;

      showToast({
        tone: failureCount > 0 ? "info" : "success",
        title: "Enrollment processed",
        message: failureCount > 0
          ? `${successCount} enrolled, ${failureCount} skipped.`
          : `${successCount} learner${successCount === 1 ? "" : "s"} enrolled successfully.`,
      });

      resetEnrollmentForm();
      setEnrollModalOpen(false);
      setRefreshTick((current) => current + 1);
    } catch {
      showToast({
        tone: "error",
        title: "Enrollment failed",
        message: "The selected learners could not be enrolled.",
      });
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDropEnrollment() {
    if (!dropTarget) {
      return;
    }

    try {
      await api.delete(`/enrollments/${dropTarget.id}`);
      showToast({
        tone: "success",
        title: "Enrollment dropped",
        message: "The learner was removed from the course.",
      });
      setDropTarget(null);
      setRefreshTick((current) => current + 1);
    } catch {
      showToast({
        tone: "error",
        title: "Drop failed",
        message: "The enrollment could not be updated.",
      });
    }
  }

  function handleSort(columnKey: string) {
    startTransition(() => {
      if (sortBy === columnKey) {
        setSortDir((current) => (current === "asc" ? "desc" : "asc"));
      } else {
        setSortBy(columnKey as NonNullable<EnrollmentListFilters["sort_by"]>);
        setSortDir("asc");
      }
      setPage(1);
    });
  }

  const columns: DataTableColumn<Enrollment>[] = [
    {
      key: "learner",
      header: "Learner",
      render: (row) => (
        <div className="min-w-0">
          <p className="truncate text-body-md font-semibold text-night-900">{row.user?.full_name ?? "Unknown learner"}</p>
          <p className="truncate text-body-sm text-neutral-500">{row.user?.email ?? "No email"}</p>
        </div>
      ),
    },
    {
      key: "course",
      header: "Course",
      render: (row) => (
        <div className="min-w-0">
          <p className="truncate text-body-md font-semibold text-night-900">{row.course?.title ?? "Unknown course"}</p>
          <p className="text-body-sm text-neutral-500">Enrollment #{row.id}</p>
        </div>
      ),
    },
    {
      key: "status",
      header: "Status",
      sortable: true,
      render: (row) => <EnrollmentStatusBadge status={row.status} />,
    },
    {
      key: "enrolled_at",
      header: "Enrolled",
      sortable: true,
      render: (row) => <span className="text-body-sm text-neutral-600">{formatDate(row.enrolled_at)}</span>,
    },
    {
      key: "due_at",
      header: "Due",
      sortable: true,
      render: (row) => <span className="text-body-sm text-neutral-600">{formatDate(row.due_at)}</span>,
    },
    {
      key: "progress",
      header: "Progress",
      cellClassName: "min-w-[180px]",
      render: (row) => <ProgressBar value={row.progress_percentage} showValue className="min-w-[160px]" />,
    },
    {
      key: "actions",
      header: "Actions",
      cellClassName: "w-[140px]",
      render: (row) =>
        hasPermission("enrollments.delete") && row.status !== "completed" && row.status !== "dropped" ? (
          <button
            type="button"
            onClick={() => setDropTarget(row)}
            className="text-body-sm font-semibold text-error-600 underline"
          >
            Drop
          </button>
        ) : (
          <span className="text-body-sm text-neutral-400">-</span>
        ),
    },
  ];

  return (
    <ProtectedRoute requiredPermissions={["enrollments.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Enrollments</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Review course assignments, active learners, and enrollment activity.
            </p>
          </div>

          {hasPermission("enrollments.create") ? (
            <Button type="button" onClick={() => setEnrollModalOpen(true)}>
              Enroll Users
            </Button>
          ) : null}
        </div>

        <div className="mt-8 rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.2fr)_220px_220px]">
            <div className="space-y-2">
              <label htmlFor="enrollment-search" className="text-body-sm font-semibold text-night-800">
                Search learner
              </label>
              <Input
                id="enrollment-search"
                value={search}
                onChange={(event) => {
                  setSearch(event.target.value);
                  startTransition(() => setPage(1));
                }}
                placeholder="Search by learner name or email"
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="enrollment-course" className="text-body-sm font-semibold text-night-800">
                Course
              </label>
              <Select
                id="enrollment-course"
                value={courseId}
                options={courseFilterOptions}
                onChange={(event) => {
                  startTransition(() => {
                    setCourseId(event.target.value);
                    setPage(1);
                  });
                }}
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="enrollment-status" className="text-body-sm font-semibold text-night-800">
                Status
              </label>
              <Select
                id="enrollment-status"
                value={status}
                options={statusOptions}
                onChange={(event) => {
                  startTransition(() => {
                    setStatus(event.target.value as EnrollmentStatus | "");
                    setPage(1);
                  });
                }}
              />
            </div>
          </div>
        </div>

        <div className="mt-6">
          <DataTable
            columns={columns}
            rows={rows}
            rowKey={(row) => row.id}
            sortBy={sortBy}
            sortDir={sortDir}
            onSort={handleSort}
            emptyState={isLoading ? "Loading enrollments..." : "No enrollments matched the current filters."}
          />
        </div>

        <div className="mt-6">
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            onPageChange={(nextPage) => startTransition(() => setPage(nextPage))}
            disabled={isLoading || isPending}
          />
        </div>
      </div>

      <Modal
        open={enrollModalOpen}
        onClose={() => {
          setEnrollModalOpen(false);
          resetEnrollmentForm();
        }}
        title="Enroll Users"
        description="Assign one published course to one or more learners."
        size="lg"
        footer={
          <>
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                setEnrollModalOpen(false);
                resetEnrollmentForm();
              }}
            >
              Cancel
            </Button>
            <Button type="button" disabled={isSubmitting} onClick={handleSubmitEnrollment}>
              {isSubmitting ? "Enrolling..." : "Submit Enrollment"}
            </Button>
          </>
        }
      >
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div className="space-y-2 md:col-span-2">
            <label htmlFor="course-search" className="text-body-sm font-semibold text-night-800">
              Search course
            </label>
            <Input
              id="course-search"
              value={courseSearch}
              onChange={(event) => setCourseSearch(event.target.value)}
              placeholder="Filter published courses by title"
            />
          </div>

          <div className="space-y-2 md:col-span-2">
            <label htmlFor="course-select" className="text-body-sm font-semibold text-night-800">
              Course
            </label>
            <Select
              id="course-select"
              value={selectedCourseId}
              options={publishedCourseOptions}
              placeholder="Select a published course"
              onChange={(event) => setSelectedCourseId(event.target.value)}
            />
          </div>

          <div className="space-y-2 md:col-span-2">
            <label htmlFor="learner-search" className="text-body-sm font-semibold text-night-800">
              Search learners
            </label>
            <Input
              id="learner-search"
              value={learnerSearch}
              onChange={(event) => setLearnerSearch(event.target.value)}
              placeholder="Search learner name or email"
            />
          </div>

          <div className="space-y-2 md:col-span-2">
            <label htmlFor="learner-select" className="text-body-sm font-semibold text-night-800">
              Learners
            </label>
            <MultiSelect
              value={selectedUserIds}
              options={learnerMultiSelectOptions}
              onChange={setSelectedUserIds}
              placeholder="Select one or more learners"
              emptyState="No matching learners found."
            />
          </div>

          <div className="space-y-2">
            <label htmlFor="due-at" className="text-body-sm font-semibold text-night-800">
              Due date
            </label>
            <Input
              id="due-at"
              type="date"
              value={dueAt}
              onChange={(event) => setDueAt(event.target.value)}
            />
          </div>
        </div>
      </Modal>

      <Modal
        open={dropTarget !== null}
        onClose={() => setDropTarget(null)}
        title="Drop enrollment"
        description="This keeps the enrollment record but marks it as dropped."
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setDropTarget(null)}>
              Cancel
            </Button>
            <Button type="button" variant="danger" onClick={handleDropEnrollment}>
              Drop Enrollment
            </Button>
          </>
        }
      >
        <p className="text-body-md text-neutral-600">
          {dropTarget
            ? `Drop ${dropTarget.user?.full_name ?? "this learner"} from ${dropTarget.course?.title ?? "this course"}?`
            : ""}
        </p>
      </Modal>
    </ProtectedRoute>
  );

  function resetEnrollmentForm() {
    setCourseSearch("");
    setSelectedCourseId("");
    setLearnerSearch("");
    setSelectedUserIds([]);
    setDueAt("");
  }
}

function EnrollmentStatusBadge({ status }: { status: EnrollmentStatus }) {
  const variants: Record<EnrollmentStatus, "info" | "success" | "neutral" | "warning"> = {
    active: "info",
    completed: "success",
    dropped: "neutral",
    expired: "warning",
  };

  const labels: Record<EnrollmentStatus, string> = {
    active: "Active",
    completed: "Completed",
    dropped: "Dropped",
    expired: "Expired",
  };

  return <Badge variant={variants[status]}>{labels[status]}</Badge>;
}

function formatDate(value: string | null): string {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
}
