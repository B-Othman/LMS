import { createApiClient } from "@securecy/config/api-client";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export const api = createApiClient(API_BASE_URL);
