import type { SearchResults } from "@securecy/types";

import { api } from "./api";

export async function search(query: string, type: "users" | "courses" | "all" = "all"): Promise<SearchResults> {
  const res = await api.get<SearchResults>("/search", {
    params: { q: query, type },
  });

  return res.data ?? { users: [], courses: [] };
}
