"use client";

import type { Role, RoleSlug, User, UserListFilters } from "@securecy/types";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useDeferredValue, useEffect, useMemo, useState, useTransition } from "react";

import {
  Avatar,
  Badge,
  Button,
  DataTable,
  Input,
  Modal,
  Pagination,
  ProtectedRoute,
  Select,
  useAuth,
  useToast,
  type DataTableColumn,
} from "@securecy/ui";

import { api } from "@/lib/api";

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const emptyMeta: PaginationMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
};

const statusOptions = [
  { label: "All statuses", value: "" },
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" },
  { label: "Suspended", value: "suspended" },
];

export function UsersPageClient() {
  const router = useRouter();
  const { hasPermission } = useAuth();
  const { showToast } = useToast();
  const [search, setSearch] = useState("");
  const deferredSearch = useDeferredValue(search);
  const [status, setStatus] = useState<User["status"] | "">("");
  const [role, setRole] = useState<RoleSlug | "">("");
  const [sortBy, setSortBy] = useState<NonNullable<UserListFilters["sort_by"]>>("created_at");
  const [sortDir, setSortDir] = useState<NonNullable<UserListFilters["sort_dir"]>>("desc");
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<User[]>([]);
  const [roles, setRoles] = useState<Role[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(emptyMeta);
  const [isLoading, setIsLoading] = useState(true);
  const [deleteTarget, setDeleteTarget] = useState<User | null>(null);
  const [refreshTick, setRefreshTick] = useState(0);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    let cancelled = false;

    api
      .paginated<Role>("/roles", { params: { per_page: 100 } })
      .then((response) => {
        if (!cancelled) {
          setRoles(response.data ?? []);
        }
      })
      .catch(() => {
        if (!cancelled) {
          showToast({
            tone: "error",
            title: "Roles unavailable",
            message: "The role list could not be loaded.",
          });
        }
      });

    return () => {
      cancelled = true;
    };
  }, [showToast]);

  useEffect(() => {
    let cancelled = false;
    setIsLoading(true);

    api
      .paginated<User>("/users", {
        params: {
          search: deferredSearch || undefined,
          status: status || undefined,
          role: role || undefined,
          sort_by: sortBy,
          sort_dir: sortDir,
          per_page: emptyMeta.per_page,
          page,
        },
      })
      .then((response) => {
        if (cancelled) {
          return;
        }

        setRows(response.data ?? []);
        setMeta((response.meta as PaginationMeta | undefined) ?? emptyMeta);
      })
      .catch(() => {
        if (!cancelled) {
          setRows([]);
          setMeta(emptyMeta);
          showToast({
            tone: "error",
            title: "Users unavailable",
            message: "The user list could not be loaded.",
          });
        }
      })
      .finally(() => {
        if (!cancelled) {
          setIsLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [deferredSearch, page, refreshTick, role, showToast, sortBy, sortDir, status]);

  const roleOptions = useMemo(
    () => [{ label: "All roles", value: "" }, ...roles.map((item) => ({ label: item.name, value: item.slug }))],
    [roles],
  );

  const columns: DataTableColumn<User>[] = [
    {
      key: "name",
      header: "Name",
      sortable: true,
      render: (user) => (
        <div className="flex items-center gap-3">
          <Avatar name={user.full_name ?? `${user.first_name} ${user.last_name}`} src={user.avatar_url} />
          <div className="min-w-0">
            <p className="truncate text-body-md font-semibold text-night-900">
              {user.full_name ?? `${user.first_name} ${user.last_name}`}
            </p>
            <p className="text-body-sm text-neutral-500">User #{user.id}</p>
          </div>
        </div>
      ),
    },
    {
      key: "email",
      header: "Email",
      sortable: true,
      render: (user) => <span className="text-body-md text-neutral-700">{user.email}</span>,
    },
    {
      key: "roles",
      header: "Roles",
      render: (user) => (
        <div className="flex flex-wrap gap-2">
          {user.roles.map((item) => (
            <Badge key={item} variant="info">
              {humanizeRole(item)}
            </Badge>
          ))}
        </div>
      ),
    },
    {
      key: "status",
      header: "Status",
      sortable: true,
      render: (user) => <Badge variant={statusBadgeVariant(user.status)}>{humanizeStatus(user.status)}</Badge>,
    },
    {
      key: "last_login_at",
      header: "Last Login",
      sortable: true,
      render: (user) => (
        <span className="text-body-sm text-neutral-500">{formatDateTime(user.last_login_at)}</span>
      ),
    },
    {
      key: "actions",
      header: "Actions",
      cellClassName: "w-[160px]",
      render: (user) => (
        <div className="flex items-center gap-2">
          <Link href={`/users/${user.id}/edit`} className="text-body-sm font-semibold text-primary-700 underline">
            Edit
          </Link>
          <button
            type="button"
            onClick={() => setDeleteTarget(user)}
            className="text-body-sm font-semibold text-error-600 underline"
          >
            Delete
          </button>
        </div>
      ),
    },
  ];

  async function handleDelete() {
    if (!deleteTarget) {
      return;
    }

    try {
      await api.delete(`/users/${deleteTarget.id}`);
      setDeleteTarget(null);
      setRows((current) => current.filter((item) => item.id !== deleteTarget.id));
      showToast({
        tone: "success",
        title: "User deleted",
        message: `${deleteTarget.full_name ?? deleteTarget.email} has been removed.`,
      });
      setRefreshTick((current) => current + 1);

      if (rows.length === 1 && meta.current_page > 1) {
        startTransition(() => {
          setPage((current) => current - 1);
        });
      }
    } catch {
      showToast({
        tone: "error",
        title: "Delete failed",
        message: "The user could not be deleted.",
      });
    }
  }

  function handleSort(columnKey: string) {
    startTransition(() => {
      if (sortBy === columnKey) {
        setSortDir((current) => (current === "asc" ? "desc" : "asc"));
      } else {
        setSortBy(columnKey as NonNullable<UserListFilters["sort_by"]>);
        setSortDir("asc");
      }

      setPage(1);
    });
  }

  return (
    <ProtectedRoute requiredPermissions={["users.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 className="text-h1 text-night-800">Users</h1>
            <p className="mt-2 text-body-lg text-neutral-500">
              Manage tenant users, role assignments, and account status.
            </p>
          </div>

          {hasPermission("users.create") ? (
            <Button type="button" onClick={() => router.push("/users/create")}>
              Add User
            </Button>
          ) : null}
        </div>

        <div className="mt-8 rounded-card border border-neutral-200 bg-white p-5 shadow-card">
          <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.3fr)_220px_220px]">
            <div className="space-y-2">
              <label htmlFor="users-search" className="text-body-sm font-semibold text-night-800">
                Search
              </label>
              <Input
                id="users-search"
                value={search}
                onChange={(event) => {
                  const nextValue = event.target.value;
                  setSearch(nextValue);
                  startTransition(() => {
                    setPage(1);
                  });
                }}
                placeholder="Search by name or email"
              />
            </div>

            <div className="space-y-2">
              <label htmlFor="users-status" className="text-body-sm font-semibold text-night-800">
                Status
              </label>
              <Select
                id="users-status"
                value={status}
                options={statusOptions}
                onChange={(event) => {
                  const nextValue = event.target.value as User["status"] | "";
                  startTransition(() => {
                    setStatus(nextValue);
                    setPage(1);
                  });
                }}
              />
            </div>

            <div className="space-y-2">
              <label htmlFor="users-role" className="text-body-sm font-semibold text-night-800">
                Role
              </label>
              <Select
                id="users-role"
                value={role}
                options={roleOptions}
                onChange={(event) => {
                  const nextValue = event.target.value as RoleSlug | "";
                  startTransition(() => {
                    setRole(nextValue);
                    setPage(1);
                  });
                }}
              />
            </div>
          </div>
        </div>

        <div className="mt-6">
          <DataTable
            columns={columns}
            rows={rows}
            rowKey={(user) => user.id}
            sortBy={sortBy}
            sortDir={sortDir}
            onSort={handleSort}
            emptyState={isLoading ? "Loading users..." : "No users matched the current filters."}
          />
        </div>

        <div className="mt-6">
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            onPageChange={(nextPage) => {
              startTransition(() => {
                setPage(nextPage);
              });
            }}
            disabled={isLoading || isPending}
          />
        </div>
      </div>

      <Modal
        open={deleteTarget !== null}
        onClose={() => setDeleteTarget(null)}
        title="Delete user"
        description="This will soft delete the account and revoke active tokens."
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
            <Button type="button" variant="danger" onClick={handleDelete}>
              Delete User
            </Button>
          </>
        }
      >
        <p className="text-body-md text-neutral-600">
          {deleteTarget
            ? `Delete ${deleteTarget.full_name ?? deleteTarget.email}?`
            : "Delete this user?"}
        </p>
      </Modal>
    </ProtectedRoute>
  );
}

function humanizeRole(role: string): string {
  return role
    .split("_")
    .map((segment) => segment[0]?.toUpperCase() + segment.slice(1))
    .join(" ");
}

function humanizeStatus(status: User["status"]): string {
  return status[0]?.toUpperCase() + status.slice(1);
}

function statusBadgeVariant(status: User["status"]): "success" | "warning" | "error" {
  if (status === "active") {
    return "success";
  }

  if (status === "inactive") {
    return "warning";
  }

  return "error";
}

function formatDateTime(value: string | null): string {
  if (!value) {
    return "Never";
  }

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  }).format(new Date(value));
}
