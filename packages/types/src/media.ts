export type MediaVisibility = "public" | "private";

export interface MediaDimensions {
  width: number;
  height: number;
}

export interface MediaFileMetadata {
  extension?: string;
  dimensions?: MediaDimensions;
  thumbnail_path?: string;
  thumbnail_mime_type?: string;
  thumbnail_dimensions?: MediaDimensions;
  [key: string]: unknown;
}

export interface MediaFile {
  id: number;
  uploaded_by: number | null;
  original_filename: string;
  mime_type: string;
  size_bytes: number;
  visibility: MediaVisibility;
  metadata: MediaFileMetadata | null;
  url: string;
  thumbnail_url: string | null;
  created_at: string;
  updated_at: string;
}
