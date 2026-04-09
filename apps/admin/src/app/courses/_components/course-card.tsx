"use client";

import type { Course } from "@securecy/types";
import Link from "next/link";
import { useRef, useState } from "react";

import { Badge, EllipsisVerticalIcon } from "@securecy/ui";

import { StatusBadge } from "./status-badge";

interface CourseCardProps {
  course: Course;
  onPublish?: (course: Course) => void;
  onArchive?: (course: Course) => void;
  onDuplicate?: (course: Course) => void;
  onDelete?: (course: Course) => void;
}

export function CourseCard({ course, onPublish, onArchive, onDuplicate, onDelete }: CourseCardProps) {
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  return (
    <div className="group relative flex flex-col overflow-hidden rounded-card border border-neutral-200 bg-white shadow-card transition-shadow hover:shadow-md">
      <div className="relative h-40 bg-neutral-100">
        {course.thumbnail_url ? (
          <img
            src={course.thumbnail_url}
            alt={course.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <div className="flex h-full items-center justify-center">
            <span className="text-h3 font-bold text-neutral-300">
              {course.title.charAt(0).toUpperCase()}
            </span>
          </div>
        )}
        <div className="absolute left-3 top-3">
          <StatusBadge status={course.status} />
        </div>
      </div>

      <div className="flex flex-1 flex-col p-4">
        <div className="mb-1 flex items-start justify-between gap-2">
          <Link
            href={`/courses/${course.id}/edit`}
            className="text-body-md font-semibold text-night-900 hover:text-primary-700"
          >
            {course.title}
          </Link>
          <div className="relative" ref={menuRef}>
            <button
              type="button"
              onClick={() => setMenuOpen(!menuOpen)}
              className="rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600"
            >
              <EllipsisVerticalIcon className="h-4 w-4" />
            </button>
            {menuOpen ? (
              <>
                <div className="fixed inset-0 z-10" onClick={() => setMenuOpen(false)} />
                <div className="absolute right-0 z-20 mt-1 w-40 rounded-lg border border-neutral-200 bg-white py-1 shadow-lg">
                  <Link
                    href={`/courses/${course.id}/edit`}
                    className="block w-full px-3 py-2 text-left text-body-sm text-neutral-700 hover:bg-neutral-50"
                    onClick={() => setMenuOpen(false)}
                  >
                    Edit
                  </Link>
                  {course.status === "draft" ? (
                    <button
                      type="button"
                      onClick={() => { onPublish?.(course); setMenuOpen(false); }}
                      className="block w-full px-3 py-2 text-left text-body-sm text-neutral-700 hover:bg-neutral-50"
                    >
                      Publish
                    </button>
                  ) : null}
                  {course.status === "published" ? (
                    <button
                      type="button"
                      onClick={() => { onArchive?.(course); setMenuOpen(false); }}
                      className="block w-full px-3 py-2 text-left text-body-sm text-neutral-700 hover:bg-neutral-50"
                    >
                      Archive
                    </button>
                  ) : null}
                  <button
                    type="button"
                    onClick={() => { onDuplicate?.(course); setMenuOpen(false); }}
                    className="block w-full px-3 py-2 text-left text-body-sm text-neutral-700 hover:bg-neutral-50"
                  >
                    Duplicate
                  </button>
                  <button
                    type="button"
                    onClick={() => { onDelete?.(course); setMenuOpen(false); }}
                    className="block w-full px-3 py-2 text-left text-body-sm text-error-600 hover:bg-error-50"
                  >
                    Delete
                  </button>
                </div>
              </>
            ) : null}
          </div>
        </div>

        {course.short_description ? (
          <p className="mb-3 line-clamp-2 text-body-sm text-neutral-500">{course.short_description}</p>
        ) : null}

        <div className="mt-auto flex items-center gap-3 border-t border-neutral-100 pt-3">
          {course.category ? (
            <Badge variant="info">{course.category.name}</Badge>
          ) : null}
          <span className="text-body-sm text-neutral-400">
            {course.module_count} {course.module_count === 1 ? "module" : "modules"}
          </span>
          <span className="text-body-sm text-neutral-400">
            {course.enrollment_count} {course.enrollment_count === 1 ? "enrolled" : "enrolled"}
          </span>
        </div>
      </div>
    </div>
  );
}
