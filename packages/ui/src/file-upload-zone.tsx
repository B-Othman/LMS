"use client";

import type { MediaFile, MediaVisibility } from "@securecy/types";
import { useEffect, useId, useRef, useState } from "react";

import { Alert } from "./alert";
import { Button } from "./button";
import { FileTextIcon, PlusIcon, VideoIcon } from "./icons";

export interface FileUploadRequestOptions {
  visibility: MediaVisibility;
  signal?: AbortSignal;
  onProgress?: (progress: number) => void;
}

export interface FileUploadZoneProps {
  allowedTypes: string[];
  uploadFile: (file: File, options: FileUploadRequestOptions) => Promise<MediaFile>;
  className?: string;
  description?: string;
  disabled?: boolean;
  label?: string;
  onChange?: (mediaFileId: number | null, mediaFile: MediaFile | null) => void;
  onUploadComplete?: (mediaFile: MediaFile) => void;
  value?: MediaFile | null;
  visibility?: MediaVisibility;
}

export function FileUploadZone({
  allowedTypes,
  uploadFile,
  className = "",
  description = "Drag and drop a file here, or browse from your device.",
  disabled = false,
  label = "Upload media",
  onChange,
  onUploadComplete,
  value = null,
  visibility = "private",
}: FileUploadZoneProps) {
  const inputId = useId();
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragActive, setDragActive] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [localMedia, setLocalMedia] = useState<MediaFile | null>(null);
  const [localPreviewUrl, setLocalPreviewUrl] = useState<string | null>(null);
  const activeMedia = localMedia ?? value;

  useEffect(() => {
    return () => {
      if (localPreviewUrl) {
        URL.revokeObjectURL(localPreviewUrl);
      }
    };
  }, [localPreviewUrl]);

  async function handleSelectedFile(file: File | null) {
    if (!file || disabled || isUploading) {
      return;
    }

    if (!matchesAllowedType(file.type, allowedTypes)) {
      setError("That file type is not supported in this field.");
      return;
    }

    if (localPreviewUrl) {
      URL.revokeObjectURL(localPreviewUrl);
      setLocalPreviewUrl(null);
    }

    if (file.type.startsWith("image/")) {
      setLocalPreviewUrl(URL.createObjectURL(file));
    }

    setError(null);
    setIsUploading(true);
    setProgress(0);

    try {
      const mediaFile = await uploadFile(file, {
        visibility,
        onProgress: setProgress,
      });

      setLocalMedia(mediaFile);
      setLocalPreviewUrl(null);
      setProgress(100);
      onChange?.(mediaFile.id, mediaFile);
      onUploadComplete?.(mediaFile);
    } catch (uploadError) {
      setError(uploadError instanceof Error ? uploadError.message : "Upload failed.");
    } finally {
      setIsUploading(false);
    }
  }

  function openFilePicker() {
    inputRef.current?.click();
  }

  function clearSelection() {
    if (localPreviewUrl) {
      URL.revokeObjectURL(localPreviewUrl);
    }

    setLocalPreviewUrl(null);
    setLocalMedia(null);
    setError(null);
    setProgress(0);
    onChange?.(null, null);

    if (inputRef.current) {
      inputRef.current.value = "";
    }
  }

  const previewKind = resolvePreviewKind(activeMedia?.mime_type ?? null);
  const previewSrc = previewKind === "image"
    ? (localPreviewUrl ?? activeMedia?.thumbnail_url ?? activeMedia?.url ?? null)
    : null;

  return (
    <div className={className}>
      <div
        className={`rounded-card border-2 border-dashed p-5 transition-colors ${
          dragActive
            ? "border-primary-500 bg-primary-50"
            : "border-neutral-300 bg-neutral-0"
        } ${disabled ? "cursor-not-allowed opacity-60" : ""}`}
        onDragEnter={(event) => {
          event.preventDefault();
          if (!disabled) {
            setDragActive(true);
          }
        }}
        onDragLeave={(event) => {
          event.preventDefault();
          setDragActive(false);
        }}
        onDragOver={(event) => {
          event.preventDefault();
        }}
        onDrop={(event) => {
          event.preventDefault();
          setDragActive(false);
          void handleSelectedFile(event.dataTransfer.files[0] ?? null);
        }}
      >
        <input
          id={inputId}
          ref={inputRef}
          type="file"
          accept={allowedTypes.join(",")}
          className="hidden"
          disabled={disabled || isUploading}
          onChange={(event) => {
            const file = event.target.files?.[0] ?? null;
            void handleSelectedFile(file);
            event.target.value = "";
          }}
        />

        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div className="space-y-2">
            <p className="text-body-md font-semibold text-night-800">{label}</p>
            <p className="text-body-sm text-neutral-500">{description}</p>
            <p className="text-body-sm text-neutral-400">
              Accepted: {formatAllowedTypes(allowedTypes)}
            </p>
            <p className="text-body-sm text-neutral-400">
              Visibility: {visibility === "private" ? "Private" : "Public"}
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              size="sm"
              variant="secondary"
              disabled={disabled || isUploading}
              onClick={openFilePicker}
            >
              <PlusIcon className="mr-2 h-4 w-4" />
              {activeMedia ? "Replace file" : "Choose file"}
            </Button>

            {activeMedia ? (
              <Button
                type="button"
                size="sm"
                variant="secondary"
                disabled={disabled || isUploading}
                onClick={clearSelection}
              >
                Clear
              </Button>
            ) : null}
          </div>
        </div>

        {isUploading ? (
          <div className="mt-5">
            <div className="flex items-center justify-between text-body-sm text-neutral-500">
              <span>Uploading...</span>
              <span>{progress}%</span>
            </div>
            <div className="mt-2 h-2 rounded-full bg-neutral-100">
              <div
                className="h-2 rounded-full bg-primary-500 transition-[width]"
                style={{ width: `${progress}%` }}
              />
            </div>
          </div>
        ) : null}

        {error ? (
          <Alert tone="error" className="mt-4">
            {error}
          </Alert>
        ) : null}

        {activeMedia || previewSrc ? (
          <div className="mt-5 rounded-card border border-neutral-200 bg-neutral-50 p-4">
            <div className="flex items-start gap-4">
              <div className="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white">
                {previewKind === "image" && previewSrc ? (
                  <img
                    src={previewSrc}
                    alt={activeMedia?.original_filename ?? "Uploaded image preview"}
                    className="h-full w-full object-cover"
                  />
                ) : previewKind === "video" ? (
                  <VideoIcon className="h-10 w-10 text-primary-500" />
                ) : (
                  <FileTextIcon className="h-10 w-10 text-warning-500" />
                )}
              </div>

              <div className="min-w-0 flex-1 space-y-1">
                <p className="truncate text-body-md font-semibold text-night-800">
                  {activeMedia?.original_filename ?? "Selected file"}
                </p>
                {activeMedia ? (
                  <>
                    <p className="text-body-sm text-neutral-500">{activeMedia.mime_type}</p>
                    <p className="text-body-sm text-neutral-500">{formatBytes(activeMedia.size_bytes)}</p>
                    <p className="text-body-sm text-primary-700">Media ID: {activeMedia.id}</p>
                  </>
                ) : null}
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}

function matchesAllowedType(fileType: string, allowedTypes: string[]): boolean {
  if (!fileType) {
    return false;
  }

  return allowedTypes.some((allowedType) => {
    if (allowedType.endsWith("/*")) {
      return fileType.startsWith(allowedType.slice(0, -1));
    }

    return fileType === allowedType;
  });
}

function resolvePreviewKind(mimeType: string | null): "image" | "video" | "document" {
  if (!mimeType) {
    return "document";
  }

  if (mimeType.startsWith("image/")) {
    return "image";
  }

  if (mimeType.startsWith("video/")) {
    return "video";
  }

  return "document";
}

function formatAllowedTypes(allowedTypes: string[]): string {
  return allowedTypes
    .map((allowedType) => allowedTypeLabel(allowedType))
    .join(", ");
}

function allowedTypeLabel(allowedType: string): string {
  const labels: Record<string, string> = {
    "video/mp4": "MP4",
    "video/webm": "WEBM",
    "application/pdf": "PDF",
    "image/jpeg": "JPEG",
    "image/png": "PNG",
    "image/webp": "WEBP",
    "image/*": "Images",
  };

  return labels[allowedType] ?? allowedType;
}

function formatBytes(sizeBytes: number): string {
  if (sizeBytes < 1024) {
    return `${sizeBytes} B`;
  }

  if (sizeBytes < 1024 * 1024) {
    return `${(sizeBytes / 1024).toFixed(1)} KB`;
  }

  if (sizeBytes < 1024 * 1024 * 1024) {
    return `${(sizeBytes / 1024 / 1024).toFixed(1)} MB`;
  }

  return `${(sizeBytes / 1024 / 1024 / 1024).toFixed(1)} GB`;
}
