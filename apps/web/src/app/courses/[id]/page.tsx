import { LearnerCourseDetailPage } from "@/components/learner-course-detail-page";

interface CourseDetailPageProps {
  params: Promise<{ id: string }>;
}

export default async function CourseDetailPage({ params }: CourseDetailPageProps) {
  const { id } = await params;

  return <LearnerCourseDetailPage courseId={id} />;
}
