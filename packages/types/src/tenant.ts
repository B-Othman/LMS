export interface Tenant {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface TenantSetting {
  id: number;
  tenantId: number;
  key: string;
  value: string | null;
  createdAt: string;
  updatedAt: string;
}
