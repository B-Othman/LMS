import { Button, Card, CardHeader, CardTitle, Input, Label, ProtectedRoute } from "@securecy/ui";

interface CourseEditPageProps {
  params: Promise<{ id: string }>;
}

export default async function CourseEditPage({ params }: CourseEditPageProps) {
  const { id } = await params;

  return (
    <ProtectedRoute
      requiredPermissions={["courses.update", "courses.create", "courses.view"]}
    >
      <div className="mx-auto max-w-4xl px-6 py-8">
        <h1 className="text-h1 text-night-800">Edit Course #{id}</h1>
        <p className="mt-2 text-body-lg text-neutral-500">
          Update course details, modules, and lessons.
        </p>

        <Card className="mt-8">
          <CardHeader>
            <CardTitle>Course Details</CardTitle>
          </CardHeader>

          <div className="space-y-4">
            <div>
              <Label htmlFor="title" required>Title</Label>
              <Input id="title" placeholder="Enter course title" className="mt-1" />
            </div>
            <div>
              <Label htmlFor="description">Description</Label>
              <Input id="description" placeholder="Enter course description" className="mt-1" />
            </div>
          </div>
        </Card>

        <Card className="mt-6">
          <CardHeader>
            <CardTitle>Modules</CardTitle>
          </CardHeader>
          <p className="text-body-md text-neutral-500">No modules yet. Add modules to structure your course content.</p>
        </Card>

        <div className="mt-6 flex gap-3">
          <Button>Save Changes</Button>
          <Button variant="secondary">Cancel</Button>
        </div>
      </div>
    </ProtectedRoute>
  );
}
