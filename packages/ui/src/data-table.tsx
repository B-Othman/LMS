import type { ReactNode } from "react";

import { ChevronDownIcon, ChevronUpIcon } from "./icons";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "./table";

export interface DataTableColumn<T> {
  key: string;
  header: ReactNode;
  sortable?: boolean;
  headerClassName?: string;
  cellClassName?: string;
  render: (row: T) => ReactNode;
}

interface DataTableProps<T> {
  columns: DataTableColumn<T>[];
  rows: T[];
  rowKey: (row: T) => string | number;
  sortBy?: string;
  sortDir?: "asc" | "desc";
  onSort?: (columnKey: string) => void;
  emptyState?: ReactNode;
}

export function DataTable<T>({
  columns,
  rows,
  rowKey,
  sortBy,
  sortDir = "asc",
  onSort,
  emptyState,
}: DataTableProps<T>) {
  return (
    <div className="overflow-hidden rounded-card border border-neutral-200 bg-white shadow-card">
      <Table className="min-w-full">
        <TableHeader className="bg-neutral-50">
          <TableRow className="border-b border-neutral-200 hover:bg-neutral-50">
            {columns.map((column) => {
              const isActiveSort = sortBy === column.key;

              return (
                <TableHead key={column.key} className={column.headerClassName}>
                  {column.sortable && onSort ? (
                    <button
                      type="button"
                      onClick={() => onSort(column.key)}
                      className="inline-flex items-center gap-2 text-left text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-600 transition-colors hover:text-primary-700"
                    >
                      <span>{column.header}</span>
                      {isActiveSort ? (
                        sortDir === "asc" ? (
                          <ChevronUpIcon className="h-4 w-4" />
                        ) : (
                          <ChevronDownIcon className="h-4 w-4" />
                        )
                      ) : (
                        <ChevronDownIcon className="h-4 w-4 opacity-40" />
                      )}
                    </button>
                  ) : (
                    <span className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-600">
                      {column.header}
                    </span>
                  )}
                </TableHead>
              );
            })}
          </TableRow>
        </TableHeader>

        <TableBody>
          {rows.length > 0 ? (
            rows.map((row) => (
              <TableRow key={rowKey(row)}>
                {columns.map((column) => (
                  <TableCell key={column.key} className={column.cellClassName}>
                    {column.render(row)}
                  </TableCell>
                ))}
              </TableRow>
            ))
          ) : (
            <TableRow>
              <TableCell colSpan={columns.length} className="px-6 py-12 text-center text-body-md text-neutral-500">
                {emptyState ?? "No records found."}
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
}
