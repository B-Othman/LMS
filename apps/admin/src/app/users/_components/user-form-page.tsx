"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import type { CreateUserPayload, Role, UpdateUserPayload, User } from "@securecy/types";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

import {
  Alert,
  Button,
  Card,
  Input,
  Label,
  MultiSelect,
  ProtectedRoute,
  Select,
  isApiClientError,
  useToast,
} from "@securecy/ui";

import { api } from "@/lib/api";

const statusOptions = [
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" },
  { label: "Suspended", value: "suspended" },
];

interface UserFormPageProps {
  mode: "create" | "edit";
  userId?: number;
}

interface FormState {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  status: User["status"];
  role_ids: string[];
}

const initialFormState: FormState = {
  first_name: "",
  last_name: "",
  email: "",
  password: "",
  status: "active",
  role_ids: [],
};

export function UserFormPage({ mode, userId }: UserFormPageProps) {
  const router = useRouter();
  const { showToast } = useToast();
  const [form, setForm] = useState<FormState>(initialFormState);
  const [roles, setRoles] = useState<Role[]>([]);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(mode === "edit");
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        const [rolesResponse, userResponse] = await Promise.all([
          api.paginated<Role>("/roles", { params: { per_page: 100 } }),
          mode === "edit" && userId ? api.get<User>(`/users/${userId}`) : Promise.resolve(null),
        ]);

        if (cancelled) {
          return;
        }

        setRoles(rolesResponse.data ?? []);

        if (userResponse?.data) {
          setForm({
            first_name: userResponse.data.first_name,
            last_name: userResponse.data.last_name,
            email: userResponse.data.email,
            password: "",
            status: userResponse.data.status,
            role_ids: (userResponse.data.role_ids ?? []).map((value) => String(value)),
          });
        }
      } catch {
        if (!cancelled) {
          setGeneralError("The user form could not be loaded.");
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [mode, userId]);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setGeneralError(null);
    setFieldErrors({});
    setIsSaving(true);

    try {
      if (mode === "create") {
        const payload: CreateUserPayload = {
          first_name: form.first_name,
          last_name: form.last_name,
          email: form.email,
          password: form.password,
          status: form.status,
          role_ids: form.role_ids.map((value) => Number(value)),
        };

        await api.post<User>("/users", payload);
      } else if (userId) {
        const payload: UpdateUserPayload = {
          first_name: form.first_name,
          last_name: form.last_name,
          email: form.email,
          status: form.status,
        };

        await api.put<User>(`/users/${userId}`, payload);
        await api.post<User>(`/users/${userId}/roles`, {
          role_ids: form.role_ids.map((value) => Number(value)),
        });
      }

      showToast({
        tone: "success",
        title: mode === "create" ? "User created" : "User updated",
        message:
          mode === "create"
            ? "The new account is ready and a welcome notification has been queued."
            : "The user profile has been updated successfully.",
      });

      router.push("/users");
      router.refresh();
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "The form could not be saved.");
      } else {
        setGeneralError("The form could not be saved.");
      }
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <ProtectedRoute requiredPermissions={[mode === "create" ? "users.create" : "users.update"]}>
      <div className="mx-auto max-w-4xl px-6 py-8">
        <div>
          <h1 className="text-h1 text-night-800">{mode === "create" ? "Add User" : "Edit User"}</h1>
          <p className="mt-2 text-body-lg text-neutral-500">
            {mode === "create"
              ? "Create a tenant user and assign their initial roles."
              : "Update account details, status, and role assignments."}
          </p>
        </div>

        <Card className="mt-8">
          <form className="space-y-6" onSubmit={handleSubmit}>
            {generalError ? <Alert tone="error">{generalError}</Alert> : null}

            {isLoading ? (
              <div className="space-y-4">
                <div className="h-12 rounded-lg bg-neutral-100" />
                <div className="h-12 rounded-lg bg-neutral-100" />
                <div className="h-12 rounded-lg bg-neutral-100" />
              </div>
            ) : (
              <>
                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="first_name">First Name</Label>
                    <Input
                      id="first_name"
                      value={form.first_name}
                      error={Boolean(fieldErrors.first_name)}
                      onChange={(event) => setForm((current) => ({ ...current, first_name: event.target.value }))}
                    />
                    {fieldErrors.first_name ? (
                      <p className="text-body-sm text-error-500">{fieldErrors.first_name}</p>
                    ) : null}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="last_name">Last Name</Label>
                    <Input
                      id="last_name"
                      value={form.last_name}
                      error={Boolean(fieldErrors.last_name)}
                      onChange={(event) => setForm((current) => ({ ...current, last_name: event.target.value }))}
                    />
                    {fieldErrors.last_name ? (
                      <p className="text-body-sm text-error-500">{fieldErrors.last_name}</p>
                    ) : null}
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      value={form.email}
                      error={Boolean(fieldErrors.email)}
                      onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))}
                    />
                    {fieldErrors.email ? <p className="text-body-sm text-error-500">{fieldErrors.email}</p> : null}
                  </div>

                  {mode === "create" ? (
                    <div className="space-y-2">
                      <Label htmlFor="password">Password</Label>
                      <Input
                        id="password"
                        type="password"
                        value={form.password}
                        error={Boolean(fieldErrors.password)}
                        onChange={(event) => setForm((current) => ({ ...current, password: event.target.value }))}
                      />
                      {fieldErrors.password ? (
                        <p className="text-body-sm text-error-500">{fieldErrors.password}</p>
                      ) : null}
                    </div>
                  ) : null}
                </div>

                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="status">Status</Label>
                    <Select
                      id="status"
                      value={form.status}
                      options={statusOptions}
                      error={Boolean(fieldErrors.status)}
                      onChange={(event) =>
                        setForm((current) => ({
                          ...current,
                          status: event.target.value as User["status"],
                        }))
                      }
                    />
                    {fieldErrors.status ? <p className="text-body-sm text-error-500">{fieldErrors.status}</p> : null}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="roles">Roles</Label>
                    <MultiSelect
                      value={form.role_ids}
                      error={Boolean(fieldErrors.role_ids)}
                      options={roles.map((role) => ({
                        label: role.name,
                        value: String(role.id),
                        description: role.description ?? undefined,
                      }))}
                      onChange={(value) => setForm((current) => ({ ...current, role_ids: value }))}
                      placeholder="Select one or more roles"
                    />
                    {fieldErrors.role_ids ? (
                      <p className="text-body-sm text-error-500">{fieldErrors.role_ids}</p>
                    ) : null}
                  </div>
                </div>
              </>
            )}

            <div className="flex flex-wrap justify-end gap-3">
              <Button type="button" variant="secondary" onClick={() => router.push("/users")}>
                Cancel
              </Button>
              <Button type="submit" disabled={isLoading || isSaving}>
                {isSaving ? "Saving..." : mode === "create" ? "Create User" : "Save Changes"}
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </ProtectedRoute>
  );
}
