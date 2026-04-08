export interface ApiResponse<T> {
  data?: T;
  meta?: Record<string, unknown>;
  message?: string;
  errors?: ApiError[];
}

export interface ApiError {
  code: string;
  message: string;
  field?: string;
}

export interface PaginatedResponse<T> {
  data?: T[];
  meta: PaginationMeta;
  message?: string;
  errors?: ApiError[];
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
