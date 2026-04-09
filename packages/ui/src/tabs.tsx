"use client";

import { type ReactNode } from "react";

export interface TabItem {
  key: string;
  label: string;
  icon?: ReactNode;
}

interface TabsProps {
  tabs: TabItem[];
  activeTab: string;
  onTabChange: (key: string) => void;
  className?: string;
}

export function Tabs({ tabs, activeTab, onTabChange, className = "" }: TabsProps) {
  return (
    <div className={`border-b border-neutral-200 ${className}`}>
      <nav className="-mb-px flex gap-6" aria-label="Tabs">
        {tabs.map((tab) => {
          const isActive = tab.key === activeTab;

          return (
            <button
              key={tab.key}
              type="button"
              onClick={() => onTabChange(tab.key)}
              className={`inline-flex items-center gap-2 border-b-2 px-1 py-3 text-button transition-colors ${
                isActive
                  ? "border-primary-500 text-primary-700"
                  : "border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700"
              }`}
            >
              {tab.icon}
              {tab.label}
            </button>
          );
        })}
      </nav>
    </div>
  );
}
