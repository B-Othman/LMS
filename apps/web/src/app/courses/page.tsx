import type { Course } from "@securecy/types";
import { Card, CardHeader, CardTitle, Badge, EmptyState } from "@securecy/ui";

export default function CoursesPage() {
  return (
    <div className="mx-auto max-w-7xl px-6 py-8">
      <h1 className="text-h1 text-night-800">Courses</h1>
      <p className="mt-2 text-body-lg text-neutral-500">
        Browse available courses and start learning.
      </p>

      <div className="mt-8">
        <EmptyState
          title="No courses available"
          description="There are no published courses at this time. Check back soon."
        />
      </div>
    </div>
  );
}
