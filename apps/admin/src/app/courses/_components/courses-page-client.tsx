"use client";

import type { Course, CourseCategory, CourseListFilters, CourseStatus } from "@securecy/types";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useDeferredValue, useEffect, useMemo, useState, useTransition } from "react";

import {
  Badge,
  Button,
  DataTable,
  GridIcon,
  Input,
  ListIcon,
  Modal,
  Pagination,
  ProtectedRoute,
  Select,
  useAuth,
  useToast,
  type DataTableColumn,
} from "@securecy/ui";

import { api } from "@/lib/api";

import { CourseCard } from "./course-card";
import { StatusBadge } from "./status-badge";

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const emptyMeta: PaginationMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
};

const statusOptions = [
  { label: "All statuses", value: "" },
  { label: "Draft", value: "draft" },
  { label: "Published", value: "published" },
  { label: "Archived", value: "archived" },
];

export function CoursesPageClient() {
  const router = useRouter();
  const { hasPermission } = useAuth();
  const { showToast } = useToast();
  const [viewMode, setViewMode] = useState<"grid" | "list">("grid");
  const [search, setSearch] = useState("");
  const deferredSearch = useDeferredValue(search);
  const [status, setStatus] = useState<CourseStatus | "">("");
  const [categoryId, setCategoryId] = useState<string>("");
  const [sortBy, setSortBy] = useState<NonNullable<CourseListFilters["sort_by"]>>("created_at");
  const [sortDir, setSortDir] = useState<NonNullable<CourseListFilters["sort_dir"]>>("desc");
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<Course[]>([]);
  const [categories, setCategories] = useState<CourseCategory[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(emptyMeta);
  const [isLoading, setIsLoading] = useState(true);
  const [deleteTarget, setDeleteTarget] = useState<Course | null>(null);
  const [refreshTick, setRefreshTick] = useState(0);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    let cancelled = false;

    api
      .get<CourseCategory[]>("/categories")
      .then((response) => {
        if (!cancelled) {
          setCategories(response.data ?? []);
        }
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    let cancelled = false;
    setIsLoading(true);

    api
      .paginated<Course>("/courses", {
        params: {
          search: deferredSearch || undefined,
          status: status || undefined,
          category_id: categoryId || undefined,
          sort_by: sortBy,
          sort_dir: sortDir,
          per_page: emptyMeta.per_page,
          page,
        },
      })
      .then((response) => {
        if (cancelled) return;
        setRows(response.data ?? []);
        setMeta((response.meta as PaginationMeta | undefined) ?? emptyMeta);
      })
      .catch(() => {
        if (!cancelled) {
          setRows([]);
          setMeta(emptyMeta);
          showToast({ tone: "error", title: "Courses unavailable", message: "The course list could not be loaded." });
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [deferredSearch, page, refreshTick, showToast, sortBy, sortDir, status, categoryId]);

  const categoryOptions = useMemo(
    () => [
      { label: "All categories", value: "" },
      ...categories.map((c) => ({ label: c.name, value: String(c.id) })),
    ],
    [categories],
  );

  async function handleAction(action: "publish" | "archive" | "duplicate", course: Course) {
    try {
      await api.post(`/courses/${course.id}/${action}`);
      showToast({
        tone: "success",
        title: `Course ${action === "publish" ? "published" : action === "archive" ? "archived" : "duplicated"}`,
        message: `"${course.title}" has been ${action === "publish" ? "published" : action === "archive" ? "archived" : "duplicated"}.`,
      });
      setRefreshTick((t) => t + 1);
    } catch {
      showToast({ tone: "error", title: "Action failed", message: `Could not ${action} the course.` });
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;

    try {
      await api.delete(`/courses/${deleteTarget.id}`);
      setDeleteTarget(null);
      showToast({ tone: "success", title: "Course deleted", message: `"${deleteTarget.title}" has been deleted.` });
      setRefreshTick((t) => t + 1);
    } catch {
      showToast({ tone: "error", title: "Delete failed", message: "The course could not be deleted." });
    }
  }

  function handleSort(columnKey: string) {
    startTransition(() => {
      if (sortBy === columnKey) {
        setSortDir((d) => (d === "asc" ? "desc" : "asc"));
      } else {
        setSortBy(columnKey as NonNullable<CourseListFilters["sort_by"]>);
        setSortDir("asc");
      }
      setPage(1);
    });
  }

  const columns: DataTableColumn<Course>[] = [
    {
      key: "title",
      header: "Course",
      sortable: true,
      render: (course) => (
        <div className="min-w-0">
          <Link
            href={`/courses/${course.id}/edit`}
            className="truncate text-body-md font-semibold text-night-900 hover:text-primary-700"
          >
            {course.title}
          </Link>
          {course.short_description ? (
            <p className="mt-0.5 truncate text-body-sm text-neutral-500">{course.short_description}</p>
          ) : null}
        </div>
      ),
    },
    {
      key: "status",
      header: "Status",
      sortable: true,
      render: (course) => <StatusBadge status={course.status} />,
    },
    {
      key: "category",
      header: "Category",
      render: (course) =>
        course.category ? <Badge variant="info">{course.category.name}</Badge> : <span className="text-body-sm text-neutral-400">—</span>,
    },
    {
      key: "modules",
      header: "Modules",
      render: (course) => <span className="text-body-sm text-neutral-600">{course.module_count}</span>,
    },
    {
      key: "enrollments",
      header: "Enrolled",
      render: (course) => <span className="text-body-sm text-neutral-600">{course.enrollment_count}</span>,
    },
    {
      key: "created_at",
      header: "Created",
      sortable: true,
      render: (course) => (
        <span className="text-body-sm text-neutral-500">{formatDate(course.created_at)}</span>
      ),
    },
    {
      key: "actions",
      header: "Actions",
      cellClassName: "w-[180px]",
      render: (course) => (
        <div className="flex items-center gap-2">
          <Link href={`/courses/${course.id}/edit`} className="text-body-sm font-semibold text-primary-700 underline">
            Edit
          </Link>
          {course.status === "draft" ? (
            <button
              type="button"
              onClick={() => handleAction("publish", course)}
              className="text-body-sm font-semibold text-success-600 underline"
            >
              Publish
            </button>
          ) : null}
          <button
            type="button"
            onClick={() => setDeleteTarget(course)}
            className="text-body-sm font-semibold text-error-600 underline"
          >
            Delete
          </button>
        </div>
      ),
    },
  ];

  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Courses</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Create and manage courses, modules, and lessons.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <div className="flex rounded-lg border border-neutral-200 bg-white">
              <button
                type="button"
                onClick={() => setViewMode("grid")}
                className={`rounded-l-lg p-2 ${viewMode === "grid" ? "bg-primary-50 text-primary-700" : "text-neutral-400 hover:text-neutral-600"}`}
              >
                <GridIcon className="h-5 w-5" />
              </button>
              <button
                type="button"
                onClick={() => setViewMode("list")}
                className={`rounded-r-lg p-2 ${viewMode === "list" ? "bg-primary-50 text-primary-700" : "text-neutral-400 hover:text-neutral-600"}`}
              >
                <ListIcon className="h-5 w-5" />
              </button>
            </div>
            {hasPermission("courses.create") ? (
              <Button type="button" onClick={() => router.push("/courses/create")}>
                Create Course
              </Button>
            ) : null}
          </div>
        </div>

        <div className="mt-8 rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.3fr)_200px_200px]">
            <div className="space-y-2">
              <label htmlFor="courses-search" className="text-body-sm font-semibold text-night-800">
                Search
              </label>
              <Input
                id="courses-search"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  startTransition(() => setPage(1));
                }}
                placeholder="Search by title"
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="courses-status" className="text-body-sm font-semibold text-night-800">
                Status
              </label>
              <Select
                id="courses-status"
                value={status}
                options={statusOptions}
                onChange={(e) => {
                  startTransition(() => {
                    setStatus(e.target.value as CourseStatus | "");
                    setPage(1);
                  });
                }}
              />
            </div>
            <div className="space-y-2">
              <label htmlFor="courses-category" className="text-body-sm font-semibold text-night-800">
                Category
              </label>
              <Select
                id="courses-category"
                value={categoryId}
                options={categoryOptions}
                onChange={(e) => {
                  startTransition(() => {
                    setCategoryId(e.target.value);
                    setPage(1);
                  });
                }}
              />
            </div>
          </div>
        </div>

        <div className="mt-6">
          {viewMode === "grid" ? (
            isLoading ? (
              <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {Array.from({ length: 6 }).map((_, i) => (
                  <div key={i} className="h-72 animate-pulse rounded-card bg-neutral-100" />
                ))}
              </div>
            ) : rows.length === 0 ? (
              <div className="py-16 text-center text-body-lg text-neutral-400">
                No courses matched the current filters.
              </div>
            ) : (
              <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {rows.map((course) => (
                  <CourseCard
                    key={course.id}
                    course={course}
                    onPublish={(c) => handleAction("publish", c)}
                    onArchive={(c) => handleAction("archive", c)}
                    onDuplicate={(c) => handleAction("duplicate", c)}
                    onDelete={setDeleteTarget}
                  />
                ))}
              </div>
            )
          ) : (
            <DataTable
              columns={columns}
              rows={rows}
              rowKey={(course) => course.id}
              sortBy={sortBy}
              sortDir={sortDir}
              onSort={handleSort}
              emptyState={isLoading ? "Loading courses..." : "No courses matched the current filters."}
            />
          )}
        </div>

        <div className="mt-6">
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            onPageChange={(p) => startTransition(() => setPage(p))}
            disabled={isLoading || isPending}
          />
        </div>
      </div>

      <Modal
        open={deleteTarget !== null}
        onClose={() => setDeleteTarget(null)}
        title="Delete course"
        description="This will soft-delete the course and all its content."
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
            <Button type="button" variant="danger" onClick={handleDelete}>
              Delete Course
            </Button>
          </>
        }
      >
        <p className="text-body-md text-neutral-600">
          {deleteTarget ? `Delete "${deleteTarget.title}"? Courses with active enrollments cannot be deleted.` : ""}
        </p>
      </Modal>
    </ProtectedRoute>
  );
}

function formatDate(value: string | null): string {
  if (!value) return "—";

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
}
