"use client";

import type { ReactNode } from "react";
import { useState } from "react";

import { Avatar } from "./avatar";
import { LogoutIcon, MenuIcon, ChevronLeftIcon } from "./icons";
import { Sidebar } from "./sidebar";

export interface NavigationItem {
  href: string;
  label: string;
  icon: ReactNode;
}

interface AppShellProps {
  brand: string;
  navigation: NavigationItem[];
  userName: string;
  userEmail: string;
  notice?: ReactNode;
  onLogout: () => Promise<void> | void;
  children: ReactNode;
}

export function AppShell({
  brand,
  navigation,
  userName,
  userEmail,
  notice,
  onLogout,
  children,
}: AppShellProps) {
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <div className="min-h-screen bg-neutral-50 text-night-900">
      <Sidebar
        brand={brand}
        items={navigation}
        collapsed={collapsed}
        onCollapseToggle={() => setCollapsed((current) => !current)}
        mobileOpen={mobileOpen}
        onMobileClose={() => setMobileOpen(false)}
        collapseIcon={<ChevronLeftIcon className="h-5 w-5" />}
      />

      <div className={`transition-[padding] duration-200 ${collapsed ? "lg:pl-24" : "lg:pl-72"}`}>
        <header className="sticky top-0 z-20 border-b border-primary-100 bg-white/95 backdrop-blur">
          <div className="flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => setMobileOpen(true)}
                className="inline-flex rounded-lg border border-primary-100 p-2 text-primary-700 transition-colors hover:bg-primary-50 lg:hidden"
                aria-label="Open navigation"
              >
                <MenuIcon className="h-5 w-5" />
              </button>
              <div>
                <p className="text-overline uppercase tracking-[0.28em] text-primary-700">Securecy</p>
                <p className="mt-1 text-body-lg font-semibold text-night-900">{brand}</p>
              </div>
            </div>

            <div className="relative">
              <button
                type="button"
                onClick={() => setMenuOpen((current) => !current)}
                className="flex items-center gap-3 rounded-xl border border-primary-100 bg-white px-3 py-2 text-left transition-colors hover:bg-primary-50"
              >
                <Avatar name={userName} size="md" />
                <div className="hidden sm:block">
                  <p className="text-body-md font-semibold text-night-900">{userName}</p>
                  <p className="text-body-sm text-neutral-500">{userEmail}</p>
                </div>
              </button>

              {menuOpen ? (
                <div className="absolute right-0 mt-2 w-56 rounded-card border border-neutral-200 bg-white p-2 shadow-card">
                  <button
                    type="button"
                    onClick={() => {
                      setMenuOpen(false);
                      void onLogout();
                    }}
                    className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-body-md text-error-700 transition-colors hover:bg-error-50"
                  >
                    <LogoutIcon className="h-4 w-4" />
                    Log out
                  </button>
                </div>
              ) : null}
            </div>
          </div>
        </header>

        <main className="px-4 py-6 sm:px-6">
          {notice ? <div className="mb-6">{notice}</div> : null}
          {children}
        </main>
      </div>
    </div>
  );
}
