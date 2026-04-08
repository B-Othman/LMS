import type { Enrollment, Course } from "@securecy/types";
import { Card, CardHeader, CardTitle, Badge, EmptyState } from "@securecy/ui";

export default function DashboardPage() {
  return (
    <div className="mx-auto max-w-7xl px-6 py-8">
      <h1 className="text-h1 text-night-800">Dashboard</h1>
      <p className="mt-2 text-body-lg text-neutral-500">
        Welcome back. Track your learning progress here.
      </p>

      <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle>Enrolled Courses</CardTitle>
          </CardHeader>
          <p className="text-metric text-primary-500">0</p>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Completed</CardTitle>
          </CardHeader>
          <p className="text-metric text-success-500">0</p>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Certificates</CardTitle>
          </CardHeader>
          <p className="text-metric text-warning-500">0</p>
        </Card>
      </div>

      <div className="mt-10">
        <h2 className="text-h2 text-night-800">My Courses</h2>
        <div className="mt-4">
          <EmptyState
            title="No courses yet"
            description="You haven't enrolled in any courses. Browse the catalog to get started."
          />
        </div>
      </div>
    </div>
  );
}
