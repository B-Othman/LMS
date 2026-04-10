"use client";

import type { ContentPackage, ContentPackageVersion, ScormScoItem } from "@securecy/types";
import { useCallback, useEffect, useRef, useState } from "react";
import { Alert, Button, Modal, useToast } from "@securecy/ui";

import { api } from "@/lib/api";

interface ScormUploadModalProps {
  courseId: number;
  onClose: () => void;
  onPublished: () => void;
}

type Step = "upload" | "processing" | "review" | "error";

const MAX_FILE_BYTES = 512 * 1024 * 1024; // 512 MB

export function ScormUploadModal({ courseId, onClose, onPublished }: ScormUploadModalProps) {
  const { showToast } = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const [step, setStep] = useState<Step>("upload");
  const [isDragging, setIsDragging] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [packageId, setPackageId] = useState<number | null>(null);
  const [pkg, setPkg] = useState<ContentPackage | null>(null);
  const [version, setVersion] = useState<ContentPackageVersion | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [isPublishing, setIsPublishing] = useState(false);
  const [fileName, setFileName] = useState("");

  // Poll for package status while processing
  useEffect(() => {
    if (packageId === null || step !== "processing") return;

    pollRef.current = setInterval(async () => {
      try {
        const res = await api.get<ContentPackage>(`/packages/${packageId}`);
        const p = res.data;
        if (!p) return;

        setPkg(p);

        if (p.status === "valid") {
          clearInterval(pollRef.current!);
          setVersion(p.version);
          setStep("review");
        } else if (p.status === "invalid" || p.status === "failed") {
          clearInterval(pollRef.current!);
          setErrorMsg(p.error_message ?? "Validation failed.");
          setStep("error");
        }
      } catch {
        // keep polling
      }
    }, 2000);

    return () => {
      if (pollRef.current) clearInterval(pollRef.current);
    };
  }, [packageId, step]);

  async function handleFile(file: File) {
    if (!file.name.toLowerCase().endsWith(".zip")) {
      setErrorMsg("Only ZIP files are accepted.");
      setStep("error");
      return;
    }

    if (file.size > MAX_FILE_BYTES) {
      setErrorMsg("File exceeds the 512 MB limit.");
      setStep("error");
      return;
    }

    setFileName(file.name);
    setUploadProgress(0);
    setStep("processing");

    try {
      const formData = new FormData();
      formData.append("file", file);

      const res = await api.post<ContentPackage>(`/courses/${courseId}/packages`, formData);
      const p = res.data;
      if (!p) throw new Error("No response data.");

      setPackageId(p.id);
      setPkg(p);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : "Upload failed.";
      setErrorMsg(msg);
      setStep("error");
    }
  }

  function handleDrop(e: React.DragEvent<HTMLDivElement>) {
    e.preventDefault();
    setIsDragging(false);
    const file = e.dataTransfer.files[0];
    if (file) void handleFile(file);
  }

  function handleInputChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (file) void handleFile(file);
  }

  async function handlePublish() {
    if (!packageId) return;
    setIsPublishing(true);

    try {
      await api.post(`/packages/${packageId}/publish`);
      showToast({ tone: "success", title: "SCORM imported", message: "Lessons created from package." });
      onPublished();
    } catch {
      showToast({ tone: "error", title: "Publish failed", message: "Could not create lessons." });
    } finally {
      setIsPublishing(false);
    }
  }

  function handleRetry() {
    setStep("upload");
    setErrorMsg(null);
    setPackageId(null);
    setPkg(null);
    setVersion(null);
    setFileName("");
    setUploadProgress(0);
    if (fileInputRef.current) fileInputRef.current.value = "";
  }

  return (
    <Modal open onClose={onClose} title="Import SCORM Package" size="lg">
      {step === "upload" ? (
        <div className="space-y-5">
          <p className="text-body-md text-neutral-600">
            Upload a SCORM 1.2 ZIP package. The system will validate the archive and parse the manifest before importing.
          </p>

          {/* Drop zone */}
          <div
            onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
            onDragLeave={() => setIsDragging(false)}
            onDrop={handleDrop}
            onClick={() => fileInputRef.current?.click()}
            className={`flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-12 transition-colors ${
              isDragging
                ? "border-primary-400 bg-primary-50"
                : "border-neutral-300 bg-neutral-50 hover:border-primary-400 hover:bg-primary-50"
            }`}
          >
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100">
              <UploadIcon className="h-6 w-6 text-primary-600" />
            </div>
            <p className="mt-3 text-body-md font-semibold text-night-800">
              Drag &amp; drop your ZIP here
            </p>
            <p className="mt-1 text-body-sm text-neutral-500">
              or click to browse — max 512 MB
            </p>
            <input
              ref={fileInputRef}
              type="file"
              accept=".zip,application/zip"
              className="hidden"
              onChange={handleInputChange}
            />
          </div>

          <div className="flex justify-end gap-3">
            <Button type="button" variant="secondary" onClick={onClose}>
              Cancel
            </Button>
          </div>
        </div>
      ) : null}

      {step === "processing" ? (
        <div className="space-y-5 py-4">
          <div className="flex items-center gap-3">
            <SpinnerIcon className="h-5 w-5 animate-spin text-primary-500" />
            <p className="text-body-md font-semibold text-night-800">Processing package…</p>
          </div>
          {fileName ? (
            <p className="text-body-sm text-neutral-500 truncate">{fileName}</p>
          ) : null}
          <StatusTracker status={pkg?.status ?? "uploaded"} />
          <p className="text-body-sm text-neutral-400">
            This may take a moment. The page will update automatically.
          </p>
        </div>
      ) : null}

      {step === "review" && version ? (
        <div className="space-y-5">
          <div className="rounded-2xl border border-success-200 bg-success-50 px-4 py-3 flex items-center gap-2">
            <CheckIcon className="h-5 w-5 flex-shrink-0 text-success-600" />
            <p className="text-body-sm font-semibold text-success-700">Package validated successfully.</p>
          </div>

          <PackageSummary pkg={pkg!} version={version} />

          <p className="text-body-sm text-neutral-500">
            Clicking <strong>Publish Import</strong> will create one lesson per SCO in the course structure.
          </p>

          <div className="flex justify-end gap-3">
            <Button type="button" variant="secondary" onClick={onClose}>
              Cancel
            </Button>
            <Button type="button" disabled={isPublishing} onClick={() => void handlePublish()}>
              {isPublishing ? "Publishing…" : "Publish Import"}
            </Button>
          </div>
        </div>
      ) : null}

      {step === "error" ? (
        <div className="space-y-5">
          <Alert tone="error">
            {errorMsg ?? "An unexpected error occurred."}
          </Alert>
          <div className="flex justify-end gap-3">
            <Button type="button" variant="secondary" onClick={onClose}>
              Close
            </Button>
            <Button type="button" onClick={handleRetry}>
              Try Again
            </Button>
          </div>
        </div>
      ) : null}
    </Modal>
  );
}

function StatusTracker({ status }: { status: string }) {
  const steps = [
    { key: "uploaded", label: "Uploaded" },
    { key: "validating", label: "Validating ZIP" },
    { key: "valid", label: "Ready" },
  ];

  const currentIndex = steps.findIndex((s) => s.key === status);

  return (
    <ol className="flex items-center gap-0">
      {steps.map((s, idx) => {
        const done = idx < currentIndex || status === "valid";
        const active = s.key === status;

        return (
          <li key={s.key} className="flex flex-1 items-center">
            <div className="flex flex-col items-center">
              <div
                className={`flex h-7 w-7 items-center justify-center rounded-full text-body-sm font-bold transition-colors ${
                  done
                    ? "bg-success-500 text-white"
                    : active
                    ? "bg-primary-500 text-white"
                    : "bg-neutral-200 text-neutral-400"
                }`}
              >
                {done ? <CheckIcon className="h-4 w-4" /> : idx + 1}
              </div>
              <span className={`mt-1 text-[11px] font-medium ${active ? "text-primary-600" : done ? "text-success-600" : "text-neutral-400"}`}>
                {s.label}
              </span>
            </div>
            {idx < steps.length - 1 ? (
              <div className={`mx-1 mb-4 h-0.5 flex-1 transition-colors ${done ? "bg-success-400" : "bg-neutral-200"}`} />
            ) : null}
          </li>
        );
      })}
    </ol>
  );
}

function PackageSummary({ pkg, version }: { pkg: ContentPackage; version: ContentPackageVersion }) {
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
        <InfoCell label="Title" value={pkg.title} />
        <InfoCell label="Standard" value="SCORM 1.2" />
        <InfoCell label="SCOs" value={String(version.sco_count)} />
        <InfoCell label="File" value={pkg.original_filename} truncate />
        <InfoCell label="Size" value={formatBytes(pkg.file_size_bytes)} />
        <InfoCell label="Version" value={`v${version.version_number}`} />
      </div>

      {version.scos.length > 0 ? (
        <div>
          <p className="mb-2 text-body-sm font-semibold text-night-800">SCO Items</p>
          <ul className="divide-y divide-neutral-100 rounded-2xl border border-neutral-200 overflow-hidden">
            {version.scos.map((sco: ScormScoItem, i: number) => (
              <li key={sco.identifier} className="flex items-center gap-3 px-4 py-2.5">
                <span className="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700">
                  {i + 1}
                </span>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-body-sm font-medium text-night-800">{sco.title || sco.identifier}</p>
                  <p className="truncate text-[11px] text-neutral-400">{sco.href}</p>
                </div>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </div>
  );
}

function InfoCell({ label, value, truncate }: { label: string; value: string; truncate?: boolean }) {
  return (
    <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-3 py-2.5">
      <p className="text-body-sm font-medium text-neutral-500">{label}</p>
      <p className={`mt-0.5 text-body-sm font-semibold text-night-900 ${truncate ? "truncate" : ""}`}>{value}</p>
    </div>
  );
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function UploadIcon({ className }: { className?: string }) {
  return (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
    </svg>
  );
}

function SpinnerIcon({ className }: { className?: string }) {
  return (
    <svg className={className} fill="none" viewBox="0 0 24 24">
      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
    </svg>
  );
}

function CheckIcon({ className }: { className?: string }) {
  return (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
    </svg>
  );
}
