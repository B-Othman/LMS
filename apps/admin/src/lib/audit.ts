import type { AuditLog, PaginatedResponse } from "@securecy/types";

import { api } from "./api";

export interface AuditLogFilters {
  action?: string;
  entity_type?: string;
  entity_id?: number;
  actor_id?: number;
  date_from?: string;
  date_to?: string;
  search?: string;
  per_page?: number;
  page?: number;
}

export async function fetchAuditLogs(filters: AuditLogFilters = {}): Promise<PaginatedResponse<AuditLog>> {
  return api.paginated<AuditLog>("/audit-logs", {
    params: {
      action: filters.action,
      entity_type: filters.entity_type,
      entity_id: filters.entity_id,
      actor_id: filters.actor_id,
      date_from: filters.date_from,
      date_to: filters.date_to,
      search: filters.search,
      per_page: filters.per_page ?? 20,
      page: filters.page ?? 1,
    },
  });
}

export async function fetchAuditLog(id: number): Promise<AuditLog> {
  const res = await api.get<AuditLog>(`/audit-logs/${id}`);

  if (!res.data) throw new Error("Audit log not found.");

  return res.data;
}

export async function fetchUserAuditTrail(userId: number, page = 1): Promise<PaginatedResponse<AuditLog>> {
  return api.paginated<AuditLog>(`/users/${userId}/audit-trail`, {
    params: { page, per_page: 20 },
  });
}
