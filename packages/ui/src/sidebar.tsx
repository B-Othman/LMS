"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import type { ReactNode } from "react";

interface SidebarItem {
  href: string;
  label: string;
  icon: ReactNode;
}

interface SidebarProps {
  brand: string;
  items: SidebarItem[];
  collapsed: boolean;
  onCollapseToggle: () => void;
  mobileOpen: boolean;
  onMobileClose: () => void;
  collapseIcon: ReactNode;
}

export function Sidebar({
  brand,
  items,
  collapsed,
  onCollapseToggle,
  mobileOpen,
  onMobileClose,
  collapseIcon,
}: SidebarProps) {
  const pathname = usePathname();

  return (
    <>
      <div
        className={`fixed inset-0 z-30 bg-night-950/30 transition-opacity lg:hidden ${mobileOpen ? "pointer-events-auto opacity-100" : "pointer-events-none opacity-0"}`}
        onClick={onMobileClose}
        aria-hidden="true"
      />

      <aside
        className={`fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-primary-100 bg-white transition-transform duration-200 lg:translate-x-0 ${
          mobileOpen ? "translate-x-0" : "-translate-x-full"
        } ${collapsed ? "lg:w-24" : "lg:w-72"}`}
      >
        <div className="flex items-center justify-between border-b border-primary-100 px-4 py-5">
          <div className={collapsed ? "lg:hidden" : ""}>
            <p className="text-overline uppercase tracking-[0.28em] text-primary-700">Securecy</p>
            <h2 className="mt-1 text-h4 text-night-900">{brand}</h2>
          </div>
          <button
            type="button"
            onClick={onCollapseToggle}
            className="hidden rounded-lg border border-primary-100 p-2 text-primary-700 transition-colors hover:bg-primary-50 lg:inline-flex"
            aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
          >
            <span className={collapsed ? "rotate-180 transform" : ""}>{collapseIcon}</span>
          </button>
        </div>

        <nav className="flex-1 space-y-1 px-3 py-4">
          {items.map((item) => {
            const isActive =
              pathname === item.href ||
              (item.href !== "/dashboard" && pathname.startsWith(`${item.href}/`));

            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={onMobileClose}
                className={`flex items-center gap-3 rounded-xl px-3 py-3 text-body-md transition-colors ${
                  isActive
                    ? "bg-primary-500 text-white shadow-card"
                    : "text-neutral-600 hover:bg-primary-50 hover:text-primary-800"
                } ${collapsed ? "lg:justify-center" : ""}`}
              >
                <span className="shrink-0">{item.icon}</span>
                <span className={collapsed ? "lg:hidden" : ""}>{item.label}</span>
              </Link>
            );
          })}
        </nav>
      </aside>
    </>
  );
}
