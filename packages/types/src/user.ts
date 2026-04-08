import type { RoleSlug } from "./role";
import type { Tenant } from "./tenant";

export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  status: "active" | "inactive" | "suspended";
  avatar_url: string | null;
  last_login_at: string | null;
  roles: RoleSlug[];
  permissions: string[];
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
  tenant_id: number;
}

export interface ForgotPasswordPayload {
  email: string;
  tenant_id: number;
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
