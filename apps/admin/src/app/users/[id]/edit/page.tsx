"use client";

import { useState } from "react";
import { Tabs } from "@securecy/ui";
import { UserFormPage } from "../../_components/user-form-page";
import { UserActivityTab } from "./_components/user-activity-tab";

const TABS = [
  { key: "profile", label: "Profile" },
  { key: "activity", label: "Activity" },
];

export default function EditUserPage({ params }: { params: { id: string } }) {
  const userId = Number(params.id);
  const [activeTab, setActiveTab] = useState("profile");

  return (
    <div className="mx-auto max-w-4xl px-6 py-6">
      <Tabs tabs={TABS} activeTab={activeTab} onTabChange={setActiveTab} className="mb-0" />
      {activeTab === "profile" ? <UserFormPage mode="edit" userId={userId} /> : null}
      {activeTab === "activity" ? (
        <div className="mt-6">
          <UserActivityTab userId={userId} />
        </div>
      ) : null}
    </div>
  );
}
