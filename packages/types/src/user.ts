import type { Role } from "./role";

export interface User {
  id: number;
  tenantId: number;
  firstName: string;
  lastName: string;
  email: string;
  emailVerifiedAt: string | null;
  isActive: boolean;
  roles: Role[];
  createdAt: string;
  updatedAt: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export interface LoginPayload {
  email: string;
  password: string;
  tenantId: number;
}

export interface RegisterPayload {
  firstName: string;
  lastName: string;
  email: string;
  password: string;
  passwordConfirmation: string;
  tenantId: number;
}
