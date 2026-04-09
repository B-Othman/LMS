"use client";

import { getFieldErrors } from "@securecy/config/api-client";
import type { Course, CourseCategory, CourseTag, CourseVisibility, CreateCoursePayload } from "@securecy/types";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

import {
  Alert,
  Button,
  Card,
  Input,
  Label,
  MultiSelect,
  ProtectedRoute,
  Select,
  isApiClientError,
  useToast,
} from "@securecy/ui";

import { api } from "@/lib/api";

const visibilityOptions = [
  { label: "Private", value: "private" },
  { label: "Public", value: "public" },
  { label: "Restricted", value: "restricted" },
];

interface CourseFormPageProps {
  mode: "create";
}

interface FormState {
  title: string;
  slug: string;
  description: string;
  short_description: string;
  visibility: CourseVisibility;
  category_id: string;
  tag_ids: string[];
}

const initialFormState: FormState = {
  title: "",
  slug: "",
  description: "",
  short_description: "",
  visibility: "private",
  category_id: "",
  tag_ids: [],
};

export function CourseFormPage({ mode }: CourseFormPageProps) {
  const router = useRouter();
  const { showToast } = useToast();
  const [form, setForm] = useState<FormState>(initialFormState);
  const [categories, setCategories] = useState<CourseCategory[]>([]);
  const [tags, setTags] = useState<CourseTag[]>([]);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [autoSlug, setAutoSlug] = useState(true);

  useEffect(() => {
    let cancelled = false;

    Promise.all([
      api.get<CourseCategory[]>("/categories").catch(() => ({ data: [] as CourseCategory[] })),
      api.paginated<CourseTag>("/categories", { params: { per_page: 100 } }).catch(() => ({ data: [] as CourseTag[] })),
    ]).then(([catRes, tagRes]) => {
      if (cancelled) return;
      setCategories(catRes.data ?? []);
      setTags(tagRes.data ?? []);
    });

    return () => {
      cancelled = true;
    };
  }, []);

  function handleTitleChange(title: string) {
    setForm((f) => ({
      ...f,
      title,
      slug: autoSlug ? slugify(title) : f.slug,
    }));
  }

  function handleSlugChange(slug: string) {
    setAutoSlug(false);
    setForm((f) => ({ ...f, slug }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setGeneralError(null);
    setFieldErrors({});
    setIsSaving(true);

    try {
      const payload: CreateCoursePayload = {
        title: form.title,
        slug: form.slug || undefined,
        description: form.description || undefined,
        short_description: form.short_description || undefined,
        visibility: form.visibility,
        category_id: form.category_id ? Number(form.category_id) : undefined,
        tag_ids: form.tag_ids.map(Number),
      };

      const response = await api.post<Course>("/courses", payload);

      showToast({
        tone: "success",
        title: "Course created",
        message: "The course has been saved as a draft.",
      });

      if (response.data?.id) {
        router.push(`/courses/${response.data.id}/edit`);
      } else {
        router.push("/courses");
      }
    } catch (error) {
      if (isApiClientError(error)) {
        setFieldErrors(getFieldErrors(error.errors));
        setGeneralError(error.errors[0]?.message ?? "The form could not be saved.");
      } else {
        setGeneralError("The form could not be saved.");
      }
    } finally {
      setIsSaving(false);
    }
  }

  const categoryOptions = [
    { label: "No category", value: "" },
    ...flattenCategories(categories),
  ];

  const tagOptions = tags.map((t) => ({
    label: t.name,
    value: String(t.id),
  }));

  return (
    <ProtectedRoute requiredPermissions={["courses.create"]}>
      <div className="mx-auto max-w-4xl px-6 py-8">
        <div>
          <h1 className="text-h1 text-night-800">Create Course</h1>
          <p className="mt-2 text-body-lg text-neutral-500">
            Start building a new course. You can add modules and lessons after saving.
          </p>
        </div>

        <Card className="mt-8">
          <form className="space-y-6" onSubmit={handleSubmit}>
            {generalError ? <Alert tone="error">{generalError}</Alert> : null}

            <div className="space-y-2">
              <Label htmlFor="title">Title</Label>
              <Input
                id="title"
                value={form.title}
                error={Boolean(fieldErrors.title)}
                onChange={(e) => handleTitleChange(e.target.value)}
                placeholder="e.g. Introduction to Cybersecurity"
              />
              {fieldErrors.title ? <p className="text-body-sm text-error-500">{fieldErrors.title}</p> : null}
            </div>

            <div className="space-y-2">
              <Label htmlFor="slug">Slug</Label>
              <Input
                id="slug"
                value={form.slug}
                error={Boolean(fieldErrors.slug)}
                onChange={(e) => handleSlugChange(e.target.value)}
                placeholder="auto-generated-from-title"
              />
              <p className="text-body-sm text-neutral-400">URL-friendly identifier. Auto-generated from title.</p>
              {fieldErrors.slug ? <p className="text-body-sm text-error-500">{fieldErrors.slug}</p> : null}
            </div>

            <div className="space-y-2">
              <Label htmlFor="short_description">Short Description</Label>
              <Input
                id="short_description"
                value={form.short_description}
                error={Boolean(fieldErrors.short_description)}
                onChange={(e) => setForm((f) => ({ ...f, short_description: e.target.value }))}
                placeholder="Brief summary shown in course cards"
              />
              {fieldErrors.short_description ? (
                <p className="text-body-sm text-error-500">{fieldErrors.short_description}</p>
              ) : null}
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <textarea
                id="description"
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                rows={5}
                className="w-full rounded-lg border border-neutral-300 px-3 py-2 text-body-md text-night-800 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                placeholder="Full course description (supports markdown)"
              />
              {fieldErrors.description ? (
                <p className="text-body-sm text-error-500">{fieldErrors.description}</p>
              ) : null}
            </div>

            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="visibility">Visibility</Label>
                <Select
                  id="visibility"
                  value={form.visibility}
                  options={visibilityOptions}
                  onChange={(e) => setForm((f) => ({ ...f, visibility: e.target.value as CourseVisibility }))}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="category">Category</Label>
                <Select
                  id="category"
                  value={form.category_id}
                  options={categoryOptions}
                  onChange={(e) => setForm((f) => ({ ...f, category_id: e.target.value }))}
                />
              </div>
            </div>

            {tagOptions.length > 0 ? (
              <div className="space-y-2">
                <Label htmlFor="tags">Tags</Label>
                <MultiSelect
                  value={form.tag_ids}
                  options={tagOptions}
                  onChange={(value) => setForm((f) => ({ ...f, tag_ids: value }))}
                  placeholder="Select tags"
                />
              </div>
            ) : null}

            <div className="flex flex-wrap justify-end gap-3">
              <Button type="button" variant="secondary" onClick={() => router.push("/courses")}>
                Cancel
              </Button>
              <Button type="submit" disabled={isSaving}>
                {isSaving ? "Creating..." : "Create Course"}
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </ProtectedRoute>
  );
}

function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^\w\s-]/g, "")
    .replace(/[\s_]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 255);
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
