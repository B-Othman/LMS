export interface Tenant {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  logo_path: string | null;
  status: "active" | "suspended";
  settings: Record<string, unknown> | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface TenantSetting {
  id: number;
  tenant_id: number;
  key: string;
  value: Record<string, unknown> | null;
  created_at: string | null;
  updated_at: string | null;
}
