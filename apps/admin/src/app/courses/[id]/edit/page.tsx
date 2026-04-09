"use client";

import { useParams } from "next/navigation";

import { CourseEditPage } from "../../_components/course-edit-page";

export default function EditCoursePage() {
  const params = useParams<{ id: string }>();

  return <CourseEditPage courseId={Number(params.id)} />;
}
