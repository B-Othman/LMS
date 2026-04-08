export interface Role {
  id: number;
  tenant_id: number | null;
  name: string;
  slug: RoleSlug;
  description: string | null;
  scope: "system" | "tenant";
}

export type RoleSlug =
  | "learner"
  | "instructor"
  | "content_manager"
  | "tenant_admin"
  | "system_admin";

export interface Permission {
  id: number;
  code: string;
  group: string;
  description: string | null;
}
