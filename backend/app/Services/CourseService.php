<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Filters\CourseFilters;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginateCourses(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        (new CourseFilters($filters))->apply($query);

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findCourse(int $id): Course
    {
        return Course::with([
            'modules.lessons.quiz' => fn ($query) => $query->withCount('questions'),
            'category',
            'certificateTemplate',
            'tags',
            'creator',
        ])
            ->withCount(['enrollments', 'modules'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function createCourse(int $userId, array $data): Course
    {
        return DB::transaction(function () use ($userId, $data) {
            $slug = $data['slug'] ?? Str::slug($data['title']);

            $course = Course::create([
                'tenant_id' => $this->tenantId(),
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'visibility' => $data['visibility'] ?? 'private',
                'category_id' => $data['category_id'] ?? null,
                'certificate_template_id' => $data['certificate_template_id'] ?? null,
                'status' => CourseStatus::Draft,
                'created_by' => $userId,
            ]);

            if (! empty($data['tag_ids'])) {
                $course->tags()->sync($data['tag_ids']);
            }

            return $this->findCourse($course->id);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            $updates = [];

            foreach ([
                'title',
                'slug',
                'description',
                'short_description',
                'visibility',
                'category_id',
                'certificate_template_id',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if ($updates !== []) {
                $course->update($updates);
            }

            if (array_key_exists('tag_ids', $data)) {
                $course->tags()->sync($data['tag_ids'] ?? []);
            }

            return $this->findCourse($course->id);
        });
    }

    public function deleteCourse(Course $course): void
    {
        if ($course->enrollments()->exists()) {
            throw new \DomainException('Cannot delete a course with active enrollments.');
        }

        $course->delete();
    }

    public function publish(Course $course): Course
    {
        $moduleWithLesson = $course->modules()->whereHas('lessons')->exists();

        if (! $moduleWithLesson) {
            throw new \DomainException('Cannot publish a course without at least one module containing a lesson.');
        }

        $course->update(['status' => CourseStatus::Published]);

        return $this->findCourse($course->id);
    }

    public function archive(Course $course): Course
    {
        $course->update(['status' => CourseStatus::Archived]);

        return $this->findCourse($course->id);
    }

    public function duplicate(Course $course): Course
    {
        return DB::transaction(function () use ($course) {
            $course->load('modules.lessons.quiz.questions.options', 'quizzes.questions.options', 'tags');

            $newCourse = $course->replicate(['slug']);
            $newCourse->title = $course->title.' (Copy)';
            $newCourse->slug = Str::slug($newCourse->title).'-'.Str::random(5);
            $newCourse->status = CourseStatus::Draft;
            $newCourse->save();

            $newCourse->tags()->sync($course->tags->pluck('id'));

            foreach ($course->modules as $module) {
                $newModule = $module->replicate();
                $newModule->course_id = $newCourse->id;
                $newModule->save();

                foreach ($module->lessons as $lesson) {
                    $newLesson = $lesson->replicate();
                    $newLesson->module_id = $newModule->id;
                    $newLesson->save();

                    if ($lesson->quiz) {
                        $this->duplicateQuiz($lesson->quiz, $newCourse->id, $newLesson->id);
                    }
                }
            }

            $standaloneQuizzes = $course->quizzes
                ->filter(fn ($quiz) => $quiz->lesson_id === null);

            foreach ($standaloneQuizzes as $quiz) {
                $this->duplicateQuiz($quiz, $newCourse->id, null);
            }

            return $this->findCourse($newCourse->id);
        });
    }

    // Modules

    public function findModule(int $id): Module
    {
        return Module::with('lessons')->withCount('lessons')->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function createModule(Course $course, array $data): Module
    {
        $sortOrder = $data['sort_order'] ?? ($course->modules()->max('sort_order') + 1);

        $module = $course->modules()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        return $this->findModule($module->id);
    }

    /** @param array<string, mixed> $data */
    public function updateModule(Module $module, array $data): Module
    {
        $module->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'sort_order' => $data['sort_order'] ?? null,
        ], fn ($v) => $v !== null));

        return $this->findModule($module->id);
    }

    public function deleteModule(Module $module): void
    {
        $module->delete();
    }

    /** @param array<int, array{id: int, sort_order: int}> $items */
    public function reorderModules(Course $course, array $items): void
    {
        DB::transaction(function () use ($course, $items) {
            foreach ($items as $item) {
                $course->modules()->where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });
    }

    // Lessons

    public function findLesson(int $id): Lesson
    {
        return Lesson::with(['resources', 'quiz' => fn ($query) => $query->withCount('questions')])->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function createLesson(Module $module, array $data): Lesson
    {
        $sortOrder = $data['sort_order'] ?? ($module->lessons()->max('sort_order') + 1);

        $lesson = $module->lessons()->create([
            'title' => $data['title'],
            'type' => $data['type'],
            'content_html' => $data['content_html'] ?? null,
            'content_json' => $data['content_json'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'sort_order' => $sortOrder,
            'is_previewable' => $data['is_previewable'] ?? false,
        ]);

        return $this->findLesson($lesson->id);
    }

    /** @param array<string, mixed> $data */
    public function updateLesson(Lesson $lesson, array $data): Lesson
    {
        $update = [];

        foreach (['title', 'type', 'content_html', 'content_json', 'duration_minutes', 'sort_order', 'is_previewable'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        $lesson->update($update);

        return $this->findLesson($lesson->id);
    }

    public function deleteLesson(Lesson $lesson): void
    {
        $lesson->delete();
    }

    /** @param array<int, array{id: int, sort_order: int}> $items */
    public function reorderLessons(Module $module, array $items): void
    {
        DB::transaction(function () use ($module, $items) {
            foreach ($items as $item) {
                $module->lessons()->where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });
    }

    private function baseQuery(): Builder
    {
        return Course::query()
            ->with(['category', 'tags', 'creator', 'certificateTemplate'])
            ->withCount(['enrollments', 'modules']);
    }

    private function duplicateQuiz(\App\Models\Quiz $quiz, int $courseId, ?int $lessonId): void
    {
        $newQuiz = $quiz->replicate();
        $newQuiz->course_id = $courseId;
        $newQuiz->lesson_id = $lessonId;
        $newQuiz->save();

        foreach ($quiz->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->quiz_id = $newQuiz->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }
    }

    private function tenantId(): int
    {
        return (int) ($this->tenantContext->tenantId() ?? 0);
    }
}
