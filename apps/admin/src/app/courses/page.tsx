import type { Course } from "@securecy/types";
import { Badge, EmptyState, Button } from "@securecy/ui";

export default function AdminCoursesPage() {
  return (
    <div className="mx-auto max-w-7xl px-6 py-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-h1 text-night-800">Courses</h1>
          <p className="mt-2 text-body-lg text-neutral-500">
            Create and manage courses, modules, and lessons.
          </p>
        </div>
        <Button>Create Course</Button>
      </div>

      <div className="mt-8">
        <EmptyState
          title="No courses yet"
          description="Create your first course to get started."
        />
      </div>
    </div>
  );
}
