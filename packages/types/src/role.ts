export interface Role {
  id: number;
  name: string;
  slug: RoleSlug;
  description: string | null;
  isSystem: boolean;
  createdAt: string;
  updatedAt: string;
}

export type RoleSlug =
  | "learner"
  | "instructor"
  | "content-manager"
  | "tenant-admin"
  | "system-admin";

export interface Permission {
  id: number;
  name: string;
  slug: string;
  group: string | null;
  description: string | null;
  createdAt: string;
  updatedAt: string;
}
