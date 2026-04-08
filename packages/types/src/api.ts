export interface ApiResponse<T> {
  data: T;
  meta?: Record<string, unknown>;
  errors?: ApiError[];
}

export interface ApiError {
  code: string;
  message: string;
  field?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
  errors?: ApiError[];
}

export interface PaginationMeta {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
}
