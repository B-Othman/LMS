import { Button, Card, CardHeader, CardTitle, ProtectedRoute } from "@securecy/ui";

export default function ReportsPage() {
  return (
    <ProtectedRoute requiredPermissions={["reports.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Reports</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              View enrollment, completion, and assessment reports.
            </p>
          </div>
          <Button variant="secondary">Export</Button>
        </div>

        <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Enrollment Report</CardTitle>
            </CardHeader>
            <p className="text-body-md text-neutral-500">Track enrollment trends across courses.</p>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Completion Report</CardTitle>
            </CardHeader>
            <p className="text-body-md text-neutral-500">Monitor course completion rates.</p>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Assessment Report</CardTitle>
            </CardHeader>
            <p className="text-body-md text-neutral-500">Review quiz scores and pass rates.</p>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Certificate Report</CardTitle>
            </CardHeader>
            <p className="text-body-md text-neutral-500">Certificates issued over time.</p>
          </Card>
        </div>
      </div>
    </ProtectedRoute>
  );
}
