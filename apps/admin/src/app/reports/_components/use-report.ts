"use client";

import type { PaginatedResponse, PaginationMeta } from "@securecy/types";
import { useCallback, useEffect, useState } from "react";

export function useReport<T>(
  fetcher: (page?: number) => Promise<PaginatedResponse<T>>,
  deps: unknown[] = [],
) {
  const [rows, setRows] = useState<T[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [page, setPage] = useState(1);

  useEffect(() => {
    setPage(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);

  useEffect(() => {
    let cancelled = false;
    setIsLoading(true);

    fetcher(page)
      .then((res) => {
        if (!cancelled) {
          setRows(res.data ?? []);
          setMeta(res.meta ?? null);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setRows([]);
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, ...deps]);

  return { rows, meta, isLoading, page, setPage };
}
