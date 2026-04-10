"use client";

import type { CreateExportPayload } from "@securecy/types";
import { useState } from "react";
import { Button, useToast } from "@securecy/ui";
import { createExport, pollExport } from "@/lib/reports";

interface ExportButtonProps {
  payload: CreateExportPayload;
  label?: string;
}

export function ExportButton({ payload, label = "Export" }: ExportButtonProps) {
  const { showToast } = useToast();
  const [isExporting, setIsExporting] = useState(false);

  async function handleExport() {
    setIsExporting(true);
    try {
      const exported = await createExport(payload);
      showToast({ tone: "info", message: `Export started — ID #${exported.id}. We'll notify you when it's ready.` });

      // Poll until ready then trigger download
      const ready = await pollExport(exported.id);
      if (ready.download_url) {
        window.open(ready.download_url, "_blank");
        showToast({ tone: "success", message: "Export ready — download started." });
      } else {
        showToast({ tone: "error", message: "Export failed. Please try again." });
      }
    } catch {
      showToast({ tone: "error", message: "Could not start export. Please try again." });
    } finally {
      setIsExporting(false);
    }
  }

  return (
    <Button variant="secondary" onClick={() => void handleExport()} disabled={isExporting}>
      {isExporting ? "Exporting…" : label}
    </Button>
  );
}
