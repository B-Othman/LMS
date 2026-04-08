import { EmptyState, ProtectedRoute } from "@securecy/ui";

export default function CoursesPage() {
  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <h1 className="text-h1 text-night-800">My Courses</h1>
        <p className="mt-2 text-body-lg text-neutral-500">
          Browse available courses and continue learning from where you left off.
        </p>

        <div className="mt-8">
          <EmptyState
            title="No courses available"
            description="There are no published courses available for your account right now."
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
