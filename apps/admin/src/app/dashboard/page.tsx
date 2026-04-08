import { Card, CardHeader, CardTitle } from "@securecy/ui";

export default function AdminDashboardPage() {
  return (
    <div className="mx-auto max-w-7xl px-6 py-8">
      <h1 className="text-h1 text-night-800">Admin Dashboard</h1>
      <p className="mt-2 text-body-lg text-neutral-500">
        Organization overview and key metrics.
      </p>

      <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-4">
        <Card>
          <CardHeader>
            <CardTitle>Total Users</CardTitle>
          </CardHeader>
          <p className="text-metric text-primary-500">0</p>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Active Courses</CardTitle>
          </CardHeader>
          <p className="text-metric text-success-500">0</p>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Enrollments</CardTitle>
          </CardHeader>
          <p className="text-metric text-warning-500">0</p>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Certificates Issued</CardTitle>
          </CardHeader>
          <p className="text-metric text-night-600">0</p>
        </Card>
      </div>
    </div>
  );
}
