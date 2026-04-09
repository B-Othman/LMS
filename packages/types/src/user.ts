import type { RoleSlug } from "./role";
import type { Tenant } from "./tenant";

export interface User {
  id: number;
  first_name: string;
  last_name: string;
  full_name?: string;
  email: string;
  status: "active" | "inactive" | "suspended";
  avatar_url: string | null;
  last_login_at: string | null;
  roles: RoleSlug[];
  role_ids?: number[];
  permissions: string[];
  enrollment_count?: number;
  tenant: Pick<Tenant, "id" | "name" | "slug"> | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export interface LoginPayload {
  email: string;
  password: string;
  tenant_id?: number;
  tenant_slug?: string;
}

export interface ForgotPasswordPayload {
  email: string;
  tenant_id?: number;
  tenant_slug?: string;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface RegisterPayload {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_id: number;
  role?: RoleSlug;
}

export interface UserListFilters {
  search?: string;
  status?: User["status"] | "";
  role?: RoleSlug | "";
  sort_by?: "name" | "email" | "status" | "last_login_at" | "created_at";
  sort_dir?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export interface CreateUserPayload {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  status: User["status"];
  role_ids: number[];
}

export interface UpdateUserPayload {
  first_name: string;
  last_name: string;
  email: string;
  status: User["status"];
}

export interface AssignUserRolesPayload {
  role_ids: number[];
}
