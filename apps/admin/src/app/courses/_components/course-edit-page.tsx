"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import type {
  CertificateTemplate,
  Course,
  CourseCategory,
  CourseVisibility,
  CreateLessonPayload,
  CreateModulePayload,
  Lesson,
  LessonContentType,
  QuizSummary,
  UpdateCoursePayload,
} from "@securecy/types";
import { useRouter } from "next/navigation";
import { useCallback, useEffect, useState } from "react";

import {
  Alert,
  Button,
  Card,
  Input,
  Label,
  ProtectedRoute,
  Select,
  Tabs,
  isApiClientError,
  useAuth,
  useToast,
  type TabItem,
  GripVerticalIcon,
  PlusIcon,
  TrashIcon,
} from "@securecy/ui";

import { api } from "@/lib/api";
import { fetchCertificateTemplates } from "@/lib/certificates";

import { QuizBuilder } from "./quiz-builder";
import { StatusBadge } from "./status-badge";

const tabItems: TabItem[] = [
  { key: "details", label: "Details" },
  { key: "structure", label: "Structure" },
  { key: "settings", label: "Settings" },
];

const visibilityOptions = [
  { label: "Private", value: "private" },
  { label: "Public", value: "public" },
  { label: "Restricted", value: "restricted" },
];

const lessonTypeOptions = [
  { label: "Text", value: "text" },
  { label: "Video", value: "video" },
  { label: "Document", value: "document" },
  { label: "Quiz", value: "quiz" },
  { label: "Assignment", value: "assignment" },
];

interface CourseEditPageProps {
  courseId: number;
}

export function CourseEditPage({ courseId }: CourseEditPageProps) {
  const router = useRouter();
  const { showToast } = useToast();
  const { hasPermission } = useAuth();
  const [activeTab, setActiveTab] = useState("details");
  const [course, setCourse] = useState<Course | null>(null);
  const [categories, setCategories] = useState<CourseCategory[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isSavingSettings, setIsSavingSettings] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [certificateTemplates, setCertificateTemplates] = useState<CertificateTemplate[]>([]);

  // Detail fields
  const [title, setTitle] = useState("");
  const [slug, setSlug] = useState("");
  const [description, setDescription] = useState("");
  const [shortDescription, setShortDescription] = useState("");
  const [visibility, setVisibility] = useState<CourseVisibility>("private");
  const [categoryId, setCategoryId] = useState("");
  const [certificateTemplateId, setCertificateTemplateId] = useState("");

  // Structure state
  const [selectedModuleId, setSelectedModuleId] = useState<number | null>(null);
  const [selectedLessonId, setSelectedLessonId] = useState<number | null>(null);

  // Add module
  const [addingModule, setAddingModule] = useState(false);
  const [newModuleTitle, setNewModuleTitle] = useState("");

  // Add lesson
  const [addingLessonModuleId, setAddingLessonModuleId] = useState<number | null>(null);
  const [newLessonTitle, setNewLessonTitle] = useState("");
  const [newLessonType, setNewLessonType] = useState<LessonContentType>("text");

  const loadCourse = useCallback(async () => {
    try {
      const [courseRes, catRes, templates] = await Promise.all([
        api.get<Course>(`/courses/${courseId}`),
        api.get<CourseCategory[]>("/categories").catch(() => ({ data: [] as CourseCategory[] })),
        fetchCertificateTemplates().catch(() => [] as CertificateTemplate[]),
      ]);

      const c = courseRes.data;
      if (!c) return;

      setCourse(c);
      setTitle(c.title);
      setSlug(c.slug);
      setDescription(c.description ?? "");
      setShortDescription(c.short_description ?? "");
      setVisibility(c.visibility);
      setCategoryId(c.category?.id ? String(c.category.id) : "");
      setCertificateTemplateId(c.certificate_template_id ? String(c.certificate_template_id) : "");
      setCategories(catRes.data ?? []);
      setCertificateTemplates(templates);

      if (c.modules?.length && !selectedModuleId) {
        setSelectedModuleId(c.modules[0].id);
      }
    } catch {
      setGeneralError("Could not load course.");
    } finally {
      setIsLoading(false);
    }
  }, [courseId, selectedModuleId]);

  useEffect(() => {
    void loadCourse();
  }, [loadCourse]);

  useEffect(() => {
    const module = course?.modules?.find((item) => item.id === selectedModuleId) ?? null;

    if (!module) {
      setSelectedLessonId(null);

      return;
    }

    if (!module.lessons.some((lesson) => lesson.id === selectedLessonId)) {
      setSelectedLessonId(module.lessons[0]?.id ?? null);
    }
  }, [course, selectedLessonId, selectedModuleId]);

  async function handleSaveDetails(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFieldErrors({});
    setGeneralError(null);
    setIsSaving(true);

    try {
      const payload: UpdateCoursePayload = {
        title,
        slug,
        description,
        short_description: shortDescription,
        visibility,
        category_id: categoryId ? Number(categoryId) : null,
      };

      const response = await api.put<Course>(`/courses/${courseId}`, payload);
      if (response.data) setCourse(response.data);

      showToast({ tone: "success", title: "Course updated", message: "Details saved." });
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "Could not save.");
      } else {
        setGeneralError("Could not save.");
      }
    } finally {
      setIsSaving(false);
    }
  }

  async function handlePublish() {
    try {
      const response = await api.post<Course>(`/courses/${courseId}/publish`);
      if (response.data) setCourse(response.data);
      showToast({ tone: "success", title: "Published", message: "Course is now live." });
    } catch {
      showToast({ tone: "error", title: "Publish failed", message: "Ensure at least one module has a lesson." });
    }
  }

  async function handleArchive() {
    try {
      const response = await api.post<Course>(`/courses/${courseId}/archive`);
      if (response.data) setCourse(response.data);
      showToast({ tone: "success", title: "Archived", message: "Course has been archived." });
    } catch {
      showToast({ tone: "error", title: "Archive failed", message: "Could not archive the course." });
    }
  }

  async function handleSaveSettings() {
    setIsSavingSettings(true);

    try {
      const response = await api.put<Course>(`/courses/${courseId}`, {
        certificate_template_id: certificateTemplateId ? Number(certificateTemplateId) : null,
      } satisfies UpdateCoursePayload);

      if (response.data) {
        setCourse(response.data);
        setCertificateTemplateId(response.data.certificate_template_id ? String(response.data.certificate_template_id) : "");
      }

      showToast({
        tone: "success",
        title: "Settings updated",
        message: "Certificate settings have been saved.",
      });
    } catch {
      showToast({
        tone: "error",
        title: "Update failed",
        message: "The course settings could not be saved.",
      });
    } finally {
      setIsSavingSettings(false);
    }
  }

  // Module actions
  async function handleAddModule() {
    if (!newModuleTitle.trim()) return;
    try {
      await api.post(`/courses/${courseId}/modules`, { title: newModuleTitle } satisfies CreateModulePayload);
      setNewModuleTitle("");
      setAddingModule(false);
      await loadCourse();
      showToast({ tone: "success", title: "Module added", message: `"${newModuleTitle}" added.` });
    } catch {
      showToast({ tone: "error", title: "Failed", message: "Could not add module." });
    }
  }

  async function handleDeleteModule(moduleId: number) {
    try {
      await api.delete(`/modules/${moduleId}`);
      if (selectedModuleId === moduleId) setSelectedModuleId(null);
      await loadCourse();
      showToast({ tone: "success", title: "Module deleted", message: "Module removed." });
    } catch {
      showToast({ tone: "error", title: "Failed", message: "Could not delete module." });
    }
  }

  // Lesson actions
  async function handleAddLesson(moduleId: number) {
    if (!newLessonTitle.trim()) return;

    const lessonTitle = newLessonTitle.trim();

    try {
      const response = await api.post<Lesson>(`/modules/${moduleId}/lessons`, {
        title: lessonTitle,
        type: newLessonType,
      } satisfies CreateLessonPayload);
      setNewLessonTitle("");
      setAddingLessonModuleId(null);
      setSelectedModuleId(moduleId);
      if (response.data) {
        setSelectedLessonId(response.data.id);
      }
      await loadCourse();
      showToast({ tone: "success", title: "Lesson added", message: `"${lessonTitle}" added.` });
    } catch {
      showToast({ tone: "error", title: "Failed", message: "Could not add lesson." });
    }
  }

  async function handleDeleteLesson(lessonId: number) {
    try {
      await api.delete(`/lessons/${lessonId}`);
      if (selectedLessonId === lessonId) {
        setSelectedLessonId(null);
      }
      await loadCourse();
      showToast({ tone: "success", title: "Lesson deleted", message: "Lesson removed." });
    } catch {
      showToast({ tone: "error", title: "Failed", message: "Could not delete lesson." });
    }
  }

  function handleQuizSummaryChange(lessonId: number, quiz: QuizSummary) {
    setCourse((current) => {
      if (!current?.modules) {
        return current;
      }

      return {
        ...current,
        modules: current.modules.map((module) => ({
          ...module,
          lessons: module.lessons.map((lesson) =>
            lesson.id === lessonId
              ? { ...lesson, quiz }
              : lesson,
          ),
        })),
      };
    });
  }

  const categoryOptions = [
    { label: "No category", value: "" },
    ...flattenCategories(categories),
  ];
  const certificateTemplateOptions = [
    { label: "Use tenant default template", value: "" },
    ...certificateTemplates
      .filter((template) => template.status === "active")
      .map((template) => ({
        label: template.is_default ? `${template.name} (Default)` : template.name,
        value: String(template.id),
      })),
  ];

  const modules = course?.modules ?? [];
  const selectedModule = modules.find((m) => m.id === selectedModuleId) ?? null;
  const selectedLesson = selectedModule?.lessons.find((lesson) => lesson.id === selectedLessonId) ?? null;

  const canPublish = course?.status === "draft" && modules.some((m) => m.lessons.length > 0);

  if (isLoading) {
    return (
      <ProtectedRoute requiredPermissions={["courses.view"]}>
        <div className="mx-auto max-w-7xl px-6 py-8">
          <div className="h-8 w-48 animate-pulse rounded bg-neutral-100" />
          <div className="mt-4 h-96 animate-pulse rounded-card bg-neutral-100" />
        </div>
      </ProtectedRoute>
    );
  }

  return (
    <ProtectedRoute requiredPermissions={["courses.view"]}>
      <div className="mx-auto max-w-7xl px-6 py-8">
        {/* Header */}
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="flex items-center gap-3">
            <h1 className="text-h1 text-night-800">{course?.title ?? "Course"}</h1>
            {course ? <StatusBadge status={course.status} /> : null}
          </div>
          <div className="flex items-center gap-3">
            {course?.status === "published" ? (
              <Button type="button" variant="secondary" size="sm" onClick={handleArchive}>
                Archive
              </Button>
            ) : null}
            <Button
              type="button"
              size="sm"
              disabled={!canPublish}
              onClick={handlePublish}
            >
              Publish
            </Button>
          </div>
        </div>

        {/* Tabs */}
        <Tabs tabs={tabItems} activeTab={activeTab} onTabChange={setActiveTab} className="mt-6" />

        {/* Details Tab */}
        {activeTab === "details" ? (
          <Card className="mt-6">
            <form className="space-y-6" onSubmit={handleSaveDetails}>
              {generalError ? <Alert tone="error">{generalError}</Alert> : null}

              <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="edit-title">Title</Label>
                  <Input
                    id="edit-title"
                    value={title}
                    error={Boolean(fieldErrors.title)}
                    onChange={(e) => setTitle(e.target.value)}
                  />
                  {fieldErrors.title ? <p className="text-body-sm text-error-500">{fieldErrors.title}</p> : null}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit-slug">Slug</Label>
                  <Input
                    id="edit-slug"
                    value={slug}
                    error={Boolean(fieldErrors.slug)}
                    onChange={(e) => setSlug(e.target.value)}
                  />
                  {fieldErrors.slug ? <p className="text-body-sm text-error-500">{fieldErrors.slug}</p> : null}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="edit-short">Short Description</Label>
                <Input
                  id="edit-short"
                  value={shortDescription}
                  onChange={(e) => setShortDescription(e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="edit-desc">Description</Label>
                <textarea
                  id="edit-desc"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={6}
                  className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-body-md text-night-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                />
              </div>

              <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="edit-vis">Visibility</Label>
                  <Select
                    id="edit-vis"
                    value={visibility}
                    options={visibilityOptions}
                    onChange={(e) => setVisibility(e.target.value as CourseVisibility)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit-cat">Category</Label>
                  <Select
                    id="edit-cat"
                    value={categoryId}
                    options={categoryOptions}
                    onChange={(e) => setCategoryId(e.target.value)}
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3">
                <Button type="button" variant="secondary" onClick={() => router.push("/courses")}>
                  Back to Courses
                </Button>
                <Button type="submit" disabled={isSaving}>
                  {isSaving ? "Saving..." : "Save Details"}
                </Button>
              </div>
            </form>
          </Card>
        ) : null}

        {/* Structure Tab */}
        {activeTab === "structure" ? (
          <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[300px_1fr]">
            {/* Module List */}
            <Card padded={false} className="overflow-hidden">
              <div className="border-b border-neutral-200 px-4 py-3">
                <h3 className="text-body-md font-semibold text-night-800">Modules</h3>
              </div>
              <div className="divide-y divide-neutral-100">
                {modules.map((mod) => (
                  <button
                    key={mod.id}
                    type="button"
                    onClick={() => setSelectedModuleId(mod.id)}
                    className={`flex w-full items-center gap-2 px-4 py-3 text-left transition-colors ${
                      selectedModuleId === mod.id ? "bg-primary-50 text-primary-700" : "text-neutral-700 hover:bg-neutral-50"
                    }`}
                  >
                    <GripVerticalIcon className="h-4 w-4 flex-shrink-0 text-neutral-300" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-body-sm font-medium">{mod.title}</p>
                      <p className="text-body-sm text-neutral-400">
                        {mod.lesson_count} {mod.lesson_count === 1 ? "lesson" : "lessons"}
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeleteModule(mod.id);
                      }}
                      className="rounded p-1 text-neutral-300 hover:text-error-500"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </button>
                ))}

                {modules.length === 0 && !addingModule ? (
                  <p className="px-4 py-6 text-center text-body-sm text-neutral-400">
                    No modules yet.
                  </p>
                ) : null}

                {addingModule ? (
                  <div className="px-4 py-3">
                    <Input
                      value={newModuleTitle}
                      onChange={(e) => setNewModuleTitle(e.target.value)}
                      placeholder="Module title"
                      onKeyDown={(e) => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          handleAddModule();
                        }
                        if (e.key === "Escape") {
                          setAddingModule(false);
                          setNewModuleTitle("");
                        }
                      }}
                      autoFocus
                    />
                    <div className="mt-2 flex gap-2">
                      <Button type="button" size="sm" onClick={handleAddModule}>
                        Add
                      </Button>
                      <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        onClick={() => { setAddingModule(false); setNewModuleTitle(""); }}
                      >
                        Cancel
                      </Button>
                    </div>
                  </div>
                ) : null}
              </div>

              <div className="border-t border-neutral-200 px-4 py-3">
                <button
                  type="button"
                  onClick={() => setAddingModule(true)}
                  className="flex items-center gap-2 text-body-sm font-medium text-primary-600 hover:text-primary-700"
                >
                  <PlusIcon className="h-4 w-4" />
                  Add Module
                </button>
              </div>
            </Card>

            {/* Lesson Panel */}
            <div className="space-y-6">
              <Card padded={false} className="overflow-hidden">
              {selectedModule ? (
                <>
                  <div className="border-b border-neutral-200 px-5 py-4">
                    <h3 className="text-body-md font-semibold text-night-800">{selectedModule.title}</h3>
                    <p className="mt-1 text-body-sm text-neutral-400">
                      {selectedModule.lesson_count} {selectedModule.lesson_count === 1 ? "lesson" : "lessons"}
                      {selectedModule.total_duration > 0 ? ` • ${selectedModule.total_duration} min` : ""}
                    </p>
                  </div>

                  <div className="divide-y divide-neutral-100">
                    {selectedModule.lessons.map((lesson) => (
                      <button
                        key={lesson.id}
                        type="button"
                        onClick={() => setSelectedLessonId(lesson.id)}
                        className={`flex w-full items-center gap-3 px-5 py-3 text-left transition-colors ${
                          selectedLessonId === lesson.id ? "bg-primary-50" : "hover:bg-neutral-50"
                        }`}
                      >
                        <GripVerticalIcon className="h-4 w-4 flex-shrink-0 text-neutral-300" />
                        <LessonTypeIcon type={lesson.type} />
                        <div className="min-w-0 flex-1">
                          <div className="flex flex-wrap items-center gap-2">
                            <p className="truncate text-body-sm font-medium text-neutral-700">{lesson.title}</p>
                            {lesson.quiz ? (
                              <span className="rounded-full bg-primary-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-primary-700">
                                {lesson.quiz.status}
                              </span>
                            ) : null}
                          </div>
                          <p className="text-body-sm text-neutral-400">
                            {lesson.type}
                            {lesson.duration_minutes ? ` • ${lesson.duration_minutes} min` : ""}
                          </p>
                        </div>
                        <button
                          type="button"
                          onClick={(event) => {
                            event.stopPropagation();
                            handleDeleteLesson(lesson.id);
                          }}
                          className="rounded p-1 text-neutral-300 hover:text-error-500"
                        >
                          <TrashIcon className="h-4 w-4" />
                        </button>
                      </button>
                    ))}

                    {selectedModule.lessons.length === 0 && addingLessonModuleId !== selectedModule.id ? (
                      <p className="px-5 py-8 text-center text-body-sm text-neutral-400">
                        No lessons in this module yet.
                      </p>
                    ) : null}

                    {addingLessonModuleId === selectedModule.id ? (
                      <div className="px-5 py-4 space-y-3">
                        <Input
                          value={newLessonTitle}
                          onChange={(e) => setNewLessonTitle(e.target.value)}
                          placeholder="Lesson title"
                          autoFocus
                          onKeyDown={(e) => {
                            if (e.key === "Enter") {
                              e.preventDefault();
                              handleAddLesson(selectedModule.id);
                            }
                            if (e.key === "Escape") {
                              setAddingLessonModuleId(null);
                              setNewLessonTitle("");
                            }
                          }}
                        />
                        <Select
                          value={newLessonType}
                          options={lessonTypeOptions}
                          onChange={(e) => setNewLessonType(e.target.value as LessonContentType)}
                        />
                        <div className="flex gap-2">
                          <Button type="button" size="sm" onClick={() => handleAddLesson(selectedModule.id)}>
                            Add Lesson
                          </Button>
                          <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            onClick={() => { setAddingLessonModuleId(null); setNewLessonTitle(""); }}
                          >
                            Cancel
                          </Button>
                        </div>
                      </div>
                    ) : null}
                  </div>

                  <div className="border-t border-neutral-200 px-5 py-3">
                    <button
                      type="button"
                      onClick={() => { setAddingLessonModuleId(selectedModule.id); setNewLessonTitle(""); setNewLessonType("text"); }}
                      className="flex items-center gap-2 text-body-sm font-medium text-primary-600 hover:text-primary-700"
                    >
                      <PlusIcon className="h-4 w-4" />
                      Add Lesson
                    </button>
                  </div>
                </>
              ) : (
                <div className="flex items-center justify-center py-16">
                  <p className="text-body-md text-neutral-400">
                    {modules.length === 0 ? "Add a module to start building course content." : "Select a module to see its lessons."}
                  </p>
                </div>
              )}
              </Card>

              {selectedLesson ? (
                selectedLesson.type === "quiz" ? (
                  <QuizBuilder
                    courseId={courseId}
                    lesson={selectedLesson}
                    onQuizSummaryChange={(quiz) => handleQuizSummaryChange(selectedLesson.id, quiz)}
                  />
                ) : (
                  <Card>
                    <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
                      Lesson Details
                    </p>
                    <h3 className="mt-3 text-h3 text-night-900">{selectedLesson.title}</h3>
                    <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                      <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                        <p className="text-body-sm font-medium text-neutral-500">Type</p>
                        <p className="mt-1 text-body-md font-semibold text-night-900">{selectedLesson.type}</p>
                      </div>
                      <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                        <p className="text-body-sm font-medium text-neutral-500">Duration</p>
                        <p className="mt-1 text-body-md font-semibold text-night-900">
                          {selectedLesson.duration_minutes ? `${selectedLesson.duration_minutes} min` : "Self-paced"}
                        </p>
                      </div>
                      <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                        <p className="text-body-sm font-medium text-neutral-500">Preview</p>
                        <p className="mt-1 text-body-md font-semibold text-night-900">
                          {selectedLesson.is_previewable ? "Enabled" : "Restricted"}
                        </p>
                      </div>
                    </div>
                    <p className="mt-5 text-body-md text-neutral-500">
                      Quiz authoring appears here only for lessons whose type is set to <span className="font-semibold text-night-900">quiz</span>.
                    </p>
                  </Card>
                )
              ) : selectedModule ? (
                <Card>
                  <p className="text-body-md text-neutral-500">
                    Select a lesson to review its details. Quiz lessons open the full builder here.
                  </p>
                </Card>
              ) : null}
            </div>
          </div>
        ) : null}

        {/* Settings Tab */}
        {activeTab === "settings" ? (
          <Card className="mt-6">
            <h3 className="text-h3 text-night-800">Course Settings</h3>
            <p className="mt-2 text-body-md text-neutral-500">
              Configure the certificate template issued when learners complete this course.
            </p>

            {hasPermission("certificates.issue") ? (
              <>
                <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,360px)_1fr]">
                  <div className="space-y-2">
                    <Label htmlFor="certificate-template">Certificate template</Label>
                    <Select
                      id="certificate-template"
                      value={certificateTemplateId}
                      options={certificateTemplateOptions}
                      onChange={(event) => setCertificateTemplateId(event.target.value)}
                    />
                  </div>

                  <div className="rounded-3xl border border-neutral-200 bg-neutral-50 px-5 py-4">
                    <p className="text-body-sm font-semibold uppercase tracking-[0.08em] text-neutral-500">
                      Issuance behavior
                    </p>
                    <p className="mt-3 text-body-md text-neutral-600">
                      If no course-specific template is selected, Securecy uses the tenant default certificate template.
                      Course completion still controls whether a certificate is issued automatically.
                    </p>
                  </div>
                </div>

                <div className="mt-6 flex justify-end">
                  <Button type="button" disabled={isSavingSettings} onClick={() => void handleSaveSettings()}>
                    {isSavingSettings ? "Saving..." : "Save Settings"}
                  </Button>
                </div>
              </>
            ) : (
              <div className="mt-6 rounded-3xl border border-neutral-200 bg-neutral-50 px-5 py-5">
                <p className="text-body-md text-neutral-600">
                  You can edit course content, but only certificate managers can assign or change certificate templates.
                </p>
              </div>
            )}
          </Card>
        ) : null}
      </div>
    </ProtectedRoute>
  );
}

function LessonTypeIcon({ type }: { type: LessonContentType }) {
  const baseClass = "h-4 w-4 flex-shrink-0";

  const colors: Record<LessonContentType, string> = {
    video: "text-primary-500",
    document: "text-warning-500",
    text: "text-neutral-500",
    quiz: "text-success-500",
    assignment: "text-error-500",
  };

  const labels: Record<LessonContentType, string> = {
    video: "▶",
    document: "📄",
    text: "T",
    quiz: "?",
    assignment: "✎",
  };

  return (
    <span className={`inline-flex items-center justify-center rounded text-body-sm font-bold ${baseClass} ${colors[type]}`}>
      {labels[type]}
    </span>
  );
}

function flattenCategories(
  categories: CourseCategory[],
  prefix = "",
): Array<{ label: string; value: string }> {
  const result: Array<{ label: string; value: string }> = [];

  for (const cat of categories) {
    result.push({ label: prefix + cat.name, value: String(cat.id) });
    if (cat.children?.length) {
      result.push(...flattenCategories(cat.children, `${prefix}${cat.name} / `));
    }
  }

  return result;
}
