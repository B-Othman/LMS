import { Badge, Button, Card, CardHeader, CardTitle, ProtectedRoute } from "@securecy/ui";

interface CourseDetailPageProps {
  params: Promise<{ id: string }>;
}

export default async function CourseDetailPage({ params }: CourseDetailPageProps) {
  const { id } = await params;

  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex items-center gap-3">
          <h1 className="text-h1 text-night-800">Course #{id}</h1>
          <Badge variant="info">Draft</Badge>
        </div>
        <p className="mt-2 text-body-lg text-neutral-500">
          Course details and modules will appear here.
        </p>

        <Card className="mt-8">
          <CardHeader>
            <CardTitle>Modules</CardTitle>
          </CardHeader>
          <p className="text-body-md text-neutral-500">No modules yet.</p>
        </Card>

        <div className="mt-6">
          <Button>Enroll in Course</Button>
        </div>
      </div>
    </ProtectedRoute>
  );
}
