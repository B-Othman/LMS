export type CourseStatus = "draft" | "published" | "archived";

export interface Course {
  id: number;
  tenantId: number;
  title: string;
  description: string;
  status: CourseStatus;
  thumbnailUrl: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface Module {
  id: number;
  courseId: number;
  title: string;
  description: string | null;
  sortOrder: number;
  createdAt: string;
  updatedAt: string;
}

export type LessonContentType = "video" | "pdf" | "text" | "quiz";

export interface Lesson {
  id: number;
  moduleId: number;
  title: string;
  contentType: LessonContentType;
  content: string | null;
  duration: number | null;
  sortOrder: number;
  createdAt: string;
  updatedAt: string;
}
