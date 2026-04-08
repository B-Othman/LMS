import { EmptyState, ProtectedRoute } from "@securecy/ui";

export default function EnrollmentsPage() {
  return (
    <ProtectedRoute requiredPermissions={["enrollments.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <h1 className="text-h1 text-night-800">Enrollments</h1>
        <p className="mt-2 text-body-lg text-neutral-500">
          Review course assignments, active learners, and enrollment activity.
        </p>

        <div className="mt-8">
          <EmptyState
            title="No enrollments yet"
            description="Enrollment activity will appear here once learners are assigned to courses."
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
