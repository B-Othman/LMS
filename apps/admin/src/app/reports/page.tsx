"use client";

import { useState } from "react";
import { ProtectedRoute, Tabs } from "@securecy/ui";
import { AssessmentsTab } from "./_components/assessments-tab";
import { CompletionsTab } from "./_components/completions-tab";
import { ExportsPanel } from "./_components/exports-panel";
import { LearnerProgressTab } from "./_components/learner-progress-tab";

const TABS = [
  { key: "completions", label: "Course Completions" },
  { key: "learner_progress", label: "Learner Progress" },
  { key: "assessments", label: "Assessment Analytics" },
];

export default function ReportsPage() {
  const [activeTab, setActiveTab] = useState("completions");
  const [showExports, setShowExports] = useState(false);

  return (
    <ProtectedRoute requiredPermissions={["reports.view"]}>
      <div className="mx-auto max-w-7xl space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-h2 font-bold text-night-900">Reports</h1>
            <p className="mt-1 text-body-md text-neutral-500">
              Analyze enrollment, completion, and assessment data.
            </p>
          </div>
          <button
            type="button"
            onClick={() => setShowExports((v) => !v)}
            className="flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 py-2 text-body-sm font-medium text-night-800 hover:bg-neutral-50 transition-colors"
          >
            <span>Exports</span>
            <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold text-neutral-600">
              History
            </span>
          </button>
        </div>

        {showExports ? (
          <ExportsPanel />
        ) : null}

        <div className="rounded-card border border-neutral-200 bg-white p-6 shadow-card">
          <Tabs tabs={TABS} activeTab={activeTab} onTabChange={setActiveTab} className="mb-6" />

          {activeTab === "completions" ? <CompletionsTab /> : null}
          {activeTab === "learner_progress" ? <LearnerProgressTab /> : null}
          {activeTab === "assessments" ? <AssessmentsTab /> : null}
        </div>
      </div>
    </ProtectedRoute>
  );
}
