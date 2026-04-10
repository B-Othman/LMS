export interface AuditActor {
  id: number;
  name: string;
  email: string;
}

export interface AuditLogChanges {
  before: Record<string, unknown>;
  after: Record<string, unknown>;
}

export interface AuditLog {
  id: number;
  action: string;
  entity_type: string | null;
  entity_id: number | null;
  description: string;
  changes: AuditLogChanges | Record<string, unknown> | null;
  ip_address: string | null;
  created_at: string;
  actor: AuditActor | null;
}
