import type { QuizSummary } from "./quiz";

export type CourseStatus = "draft" | "published" | "archived";
export type CourseVisibility = "public" | "private" | "restricted";
export type LessonContentType = "video" | "document" | "text" | "quiz" | "assignment";
export type ResourceType = "primary" | "supplementary" | "download";

export interface CourseCategory {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  sort_order: number;
  course_count: number;
  children: CourseCategory[];
  created_at: string;
  updated_at: string;
}

export interface CourseTag {
  id: number;
  name: string;
  slug: string;
}

export interface Course {
  id: number;
  title: string;
  slug: string;
  description?: string;
  short_description: string | null;
  status: CourseStatus;
  visibility: CourseVisibility;
  thumbnail_url: string | null;
  category: { id: number; name: string; slug: string } | null;
  tags: CourseTag[];
  enrollment_count: number;
  module_count: number;
  creator: { id: number; full_name: string } | null;
  certificate_template_id?: number | null;
  certificate_template?: {
    id: number;
    name: string;
    layout: "landscape" | "portrait";
    status: "active" | "inactive";
    is_default: boolean;
  } | null;
  modules?: Module[];
  created_at: string;
  updated_at: string;
}

export interface Module {
  id: number;
  course_id: number;
  title: string;
  description: string | null;
  sort_order: number;
  lesson_count: number;
  total_duration: number;
  lessons: Lesson[];
  created_at: string;
  updated_at: string;
}

export interface Lesson {
  id: number;
  module_id: number;
  title: string;
  type: LessonContentType;
  content_html: string | null;
  content_json: Record<string, unknown> | null;
  duration_minutes: number | null;
  sort_order: number;
  is_previewable: boolean;
  quiz?: QuizSummary | null;
  resources: LessonResource[];
  created_at: string;
  updated_at: string;
}

export interface LessonResource {
  id: number;
  lesson_id: number;
  media_file_id: number | null;
  label: string;
  resource_type: ResourceType;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

export interface CourseListFilters {
  search?: string;
  status?: CourseStatus | "";
  visibility?: CourseVisibility | "";
  category_id?: number | "";
  sort_by?: "title" | "status" | "created_at" | "updated_at";
  sort_dir?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export interface CreateCoursePayload {
  title: string;
  slug?: string;
  description?: string;
  short_description?: string;
  visibility?: CourseVisibility;
  category_id?: number | null;
  certificate_template_id?: number | null;
  tag_ids?: number[];
}

export interface UpdateCoursePayload {
  title?: string;
  slug?: string;
  description?: string;
  short_description?: string;
  visibility?: CourseVisibility;
  category_id?: number | null;
  certificate_template_id?: number | null;
  tag_ids?: number[];
}

export interface CreateModulePayload {
  title: string;
  description?: string;
  sort_order?: number;
}

export interface UpdateModulePayload {
  title?: string;
  description?: string;
  sort_order?: number;
}

export interface CreateLessonPayload {
  title: string;
  type: LessonContentType;
  content_html?: string;
  content_json?: Record<string, unknown>;
  duration_minutes?: number;
  sort_order?: number;
  is_previewable?: boolean;
}

export interface UpdateLessonPayload {
  title?: string;
  type?: LessonContentType;
  content_html?: string;
  content_json?: Record<string, unknown>;
  duration_minutes?: number;
  sort_order?: number;
  is_previewable?: boolean;
}
