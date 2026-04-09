"use client";

import { FileUploadZone as UiFileUploadZone, type FileUploadZoneProps as UiFileUploadZoneProps } from "@securecy/ui";

import { uploadMediaFile } from "@/lib/api";

export type FileUploadZoneProps = Omit<UiFileUploadZoneProps, "uploadFile">;

export function FileUploadZone(props: FileUploadZoneProps) {
  return (
    <UiFileUploadZone
      {...props}
      uploadFile={uploadMediaFile}
    />
  );
}
