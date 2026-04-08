import { Button, EmptyState, ProtectedRoute } from "@securecy/ui";

export default function UsersPage() {
  return (
    <ProtectedRoute requiredPermissions={["users.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Users</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Manage organization users and role assignments.
            </p>
          </div>
          <Button>Add User</Button>
        </div>

        <div className="mt-8">
          <EmptyState
            title="No users found"
            description="There are no users in this organization yet."
          />
        </div>
      </div>
    </ProtectedRoute>
  );
}
