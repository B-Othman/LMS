<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\LessonProgressStatus;
use App\Events\CourseCompleted;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MediaFile;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    public function __construct(
        private readonly MediaUploadService $mediaUploads,
    ) {}

    public function prepareEnrollmentSummary(Enrollment $enrollment): Enrollment
    {
        $enrollment->loadMissing('course', 'lessonProgress');

        $this->attachProgressSummary($enrollment);
        $this->attachNavigationHints($enrollment);

        return $enrollment;
    }

    public function prepareEnrollmentDetail(Enrollment $enrollment): Enrollment
    {
        $enrollment->loadMissing(
            'course.creator',
            'course.modules.lessons',
            'lessonProgress',
        );

        $enrollment->course?->load([
            'modules.lessons.quiz' => fn ($query) => $query->withCount('questions'),
        ]);

        $this->attachProgressSummary($enrollment);
        $this->attachLessonProgress($enrollment);
        $this->attachQuizMetadata($enrollment);
        $this->attachNavigationHints($enrollment);

        return $enrollment;
    }

    public function findUserEnrollmentProgress(User $user, int $enrollmentId): Enrollment
    {
        $enrollment = Enrollment::query()
            ->where('id', $enrollmentId)
            ->where('user_id', $user->id)
            ->whereNotIn('status', [EnrollmentStatus::Dropped->value])
            ->firstOrFail();

        $this->expireEnrollmentIfPastDue($enrollment);

        return $this->prepareEnrollmentDetail($enrollment);
    }

    public function resolveActiveEnrollmentForLesson(User $user, Lesson $lesson): Enrollment
    {
        return $this->resolveEnrollmentForLesson($user, $lesson, true);
    }

    public function resolveAccessibleEnrollmentForLesson(User $user, Lesson $lesson): Enrollment
    {
        return $this->resolveEnrollmentForLesson($user, $lesson, false);
    }

    public function startLesson(Enrollment $enrollment, Lesson $lesson): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $lesson) {
            $this->ensureLessonBelongsToEnrollment($enrollment, $lesson);

            $progress = $this->firstOrCreateProgress($enrollment, $lesson);

            if ($progress->status !== LessonProgressStatus::Completed) {
                $progress->status = LessonProgressStatus::InProgress;
                $progress->started_at ??= now();
                $progress->progress_percent = max(0, min(99, (int) $progress->progress_percent));
            }

            $progress->last_accessed_at = now();
            $progress->save();
            $enrollment->touch();

            return $this->prepareEnrollmentDetail($enrollment->fresh() ?? $enrollment);
        });
    }

    public function completeLesson(Enrollment $enrollment, Lesson $lesson): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $lesson) {
            $this->ensureLessonBelongsToEnrollment($enrollment, $lesson);

            $progress = $this->firstOrCreateProgress($enrollment, $lesson);
            $progress->status = LessonProgressStatus::Completed;
            $progress->started_at ??= now();
            $progress->completed_at ??= now();
            $progress->last_accessed_at = now();
            $progress->progress_percent = 100;
            $progress->save();

            return $this->prepareEnrollmentDetail($this->recalculateCourseProgress($enrollment));
        });
    }

    public function recordHeartbeat(Enrollment $enrollment, Lesson $lesson, int $seconds): LessonProgress
    {
        return DB::transaction(function () use ($enrollment, $lesson, $seconds) {
            $this->ensureLessonBelongsToEnrollment($enrollment, $lesson);

            $progress = $this->firstOrCreateProgress($enrollment, $lesson);

            if ($progress->status === LessonProgressStatus::NotStarted) {
                $progress->status = LessonProgressStatus::InProgress;
                $progress->started_at ??= now();
            }

            $progress->time_spent_seconds += max(1, $seconds);
            $progress->last_accessed_at = now();
            $progress->save();

            $enrollment->touch();

            return $progress->fresh() ?? $progress;
        });
    }

    public function recalculateCourseProgress(Enrollment $enrollment): Enrollment
    {
        $totalLessons = $this->totalLessons($enrollment);
        $completedLessons = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', LessonProgressStatus::Completed->value)
            ->count();

        $progressPercent = $totalLessons > 0
            ? (int) round(($completedLessons / $totalLessons) * 100)
            : 0;

        $wasCompleted = $enrollment->status === EnrollmentStatus::Completed;

        $updates = [
            'progress_percent' => $progressPercent,
            'completed_lessons_count' => $completedLessons,
        ];

        if ($progressPercent >= 100 && $totalLessons > 0) {
            $updates['status'] = EnrollmentStatus::Completed;
            $updates['completed_at'] = $enrollment->completed_at ?? now();
        } elseif ($enrollment->status === EnrollmentStatus::Completed) {
            $updates['status'] = EnrollmentStatus::Active;
            $updates['completed_at'] = null;
        }

        $enrollment->fill($updates);
        $enrollment->save();

        if (! $wasCompleted && $enrollment->status === EnrollmentStatus::Completed) {
            DB::afterCommit(function () use ($enrollment) {
                event(new CourseCompleted(
                    $enrollment->fresh()?->loadMissing('user', 'course') ?? $enrollment->loadMissing('user', 'course'),
                ));
            });
        }

        return $enrollment->fresh() ?? $enrollment;
    }

    /** @return array<string, mixed> */
    public function lessonContentForUser(User $user, Lesson $lesson): array
    {
        $this->resolveAccessibleEnrollmentForLesson($user, $lesson);

        $lesson->loadMissing('resources.mediaFile');

        $mediaFile = $this->primaryMediaFile($lesson);

        if (! $lesson->isText() && ! $mediaFile) {
            throw new \DomainException('This lesson does not have any media attached yet.');
        }

        return [
            'id' => $lesson->id,
            'module_id' => $lesson->module_id,
            'title' => $lesson->title,
            'type' => $lesson->type->value,
            'duration_minutes' => $lesson->duration_minutes,
            'content_html' => $lesson->isText() ? $lesson->content_html : null,
            'content_url' => $mediaFile ? $mediaFile->url() : null,
            'download_url' => $mediaFile
                ? ($mediaFile->visibility->isPublic()
                    ? $mediaFile->url()
                    : $this->mediaUploads->generateSignedUrl(
                        $mediaFile,
                        now()->addMinutes((int) config('media.signed_url_expiry_minutes', 15)),
                        true,
                    ))
                : null,
            'mime_type' => $mediaFile?->mime_type,
            'metadata' => $mediaFile?->metadata,
            'media' => $mediaFile ? [
                'id' => $mediaFile->id,
                'original_filename' => $mediaFile->original_filename,
                'mime_type' => $mediaFile->mime_type,
                'size_bytes' => $mediaFile->size_bytes,
                'url' => $mediaFile->url(),
                'download_url' => $mediaFile->visibility->isPublic()
                    ? $mediaFile->url()
                    : $this->mediaUploads->generateSignedUrl(
                        $mediaFile,
                        now()->addMinutes((int) config('media.signed_url_expiry_minutes', 15)),
                        true,
                    ),
                'thumbnail_url' => $mediaFile->thumbnailUrl(),
                'metadata' => $mediaFile->metadata,
            ] : null,
        ];
    }

    public function attachProgressSummary(Enrollment $enrollment): Enrollment
    {
        $course = $enrollment->course;

        if (! $course) {
            return $enrollment;
        }

        $progressRecords = $this->progressRecords($enrollment);
        $totalLessons = $this->totalLessons($enrollment);
        $completedLessons = $progressRecords
            ->where('status', LessonProgressStatus::Completed)
            ->count();
        $inProgressLessons = $progressRecords
            ->where('status', LessonProgressStatus::InProgress)
            ->count();

        if ($enrollment->status === EnrollmentStatus::Completed && $completedLessons === 0 && $totalLessons > 0) {
            $completedLessons = $totalLessons;
            $inProgressLessons = 0;
        }

        $storedCompletedLessons = (int) ($enrollment->completed_lessons_count ?? 0);
        $completedLessons = max($completedLessons, $storedCompletedLessons);

        $progressPercentage = $totalLessons > 0
            ? (int) round(($completedLessons / $totalLessons) * 100)
            : (int) ($enrollment->progress_percent ?? 0);

        $progressPercentage = max($progressPercentage, (int) ($enrollment->progress_percent ?? 0));

        if ($enrollment->status === EnrollmentStatus::Completed) {
            $progressPercentage = 100;
            $completedLessons = $totalLessons;
            $inProgressLessons = 0;
        }

        $enrollment->setAttribute('progress_summary', [
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'in_progress_lessons' => $inProgressLessons,
            'not_started_lessons' => max(0, $totalLessons - $completedLessons - $inProgressLessons),
            'progress_percentage' => $progressPercentage,
        ]);

        return $enrollment;
    }

    public function attachLessonProgress(Enrollment $enrollment): Enrollment
    {
        $course = $enrollment->course;

        if (! $course || ! $course->relationLoaded('modules')) {
            return $enrollment;
        }

        $progressByLesson = $this->progressRecords($enrollment)->keyBy('lesson_id');

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                /** @var LessonProgress|null $progress */
                $progress = $progressByLesson->get($lesson->id);

                $lesson->setAttribute('progress', [
                    'lesson_id' => $lesson->id,
                    'status' => $progress?->status?->value
                        ?? ($enrollment->status === EnrollmentStatus::Completed
                            ? LessonProgressStatus::Completed->value
                            : LessonProgressStatus::NotStarted->value),
                    'started_at' => $progress?->started_at?->toIso8601String()
                        ?? ($enrollment->status === EnrollmentStatus::Completed
                            ? $enrollment->enrolled_at?->toIso8601String()
                            : null),
                    'completed_at' => $progress?->completed_at?->toIso8601String()
                        ?? ($enrollment->status === EnrollmentStatus::Completed
                            ? $enrollment->completed_at?->toIso8601String()
                            : null),
                    'progress_percentage' => $progress
                        ? (int) $progress->progress_percent
                        : ($enrollment->status === EnrollmentStatus::Completed ? 100 : 0),
                    'last_accessed_at' => $progress?->last_accessed_at?->toIso8601String(),
                    'time_spent_seconds' => (int) ($progress?->time_spent_seconds ?? 0),
                ]);
            }
        }

        return $enrollment;
    }

    private function attachNavigationHints(Enrollment $enrollment): Enrollment
    {
        $lastAccessedLessonId = $enrollment->lessonProgress()
            ->whereNotNull('last_accessed_at')
            ->orderByDesc('last_accessed_at')
            ->value('lesson_id');

        $currentLessonId = $enrollment->lessonProgress()
            ->where('status', LessonProgressStatus::InProgress->value)
            ->orderByDesc('last_accessed_at')
            ->value('lesson_id');

        $completedLessonIds = $enrollment->lessonProgress()
            ->where('status', LessonProgressStatus::Completed->value)
            ->pluck('lesson_id');

        $firstLessonId = $this->orderedLessonsQuery($enrollment->course_id)->value('lessons.id');

        $nextLessonId = $currentLessonId
            ?: $this->orderedLessonsQuery($enrollment->course_id)
                ->when(
                    $completedLessonIds->isNotEmpty(),
                    fn (Builder $query) => $query->whereNotIn('lessons.id', $completedLessonIds->all()),
                )
                ->value('lessons.id')
            ?: $firstLessonId;

        $enrollment->setAttribute('last_accessed_lesson_id', $lastAccessedLessonId);
        $enrollment->setAttribute('next_lesson_id', $nextLessonId);

        return $enrollment;
    }

    private function attachQuizMetadata(Enrollment $enrollment): Enrollment
    {
        $course = $enrollment->course;

        if (! $course || ! $course->relationLoaded('modules')) {
            return $enrollment;
        }

        $quizIds = $course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->map(fn ($lesson) => $lesson->quiz?->id)
            ->filter()
            ->values();

        if ($quizIds->isEmpty()) {
            return $enrollment;
        }

        $attemptsByQuiz = QuizAttempt::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('quiz_id', $quizIds->all())
            ->orderByDesc('started_at')
            ->get()
            ->groupBy('quiz_id');

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if (! $lesson->relationLoaded('quiz') || ! $lesson->quiz) {
                    continue;
                }

                $quizAttempts = $attemptsByQuiz->get($lesson->quiz->id, collect());
                $attemptsUsed = $quizAttempts->count();
                $latestAttempt = $quizAttempts->first();

                $lesson->quiz->setAttribute('attempts_used', $attemptsUsed);
                $lesson->quiz->setAttribute(
                    'attempts_remaining',
                    $lesson->quiz->attempts_allowed === 0
                        ? null
                        : max(0, (int) $lesson->quiz->attempts_allowed - $attemptsUsed),
                );
                $lesson->quiz->setAttribute('latest_attempt', $latestAttempt ? [
                    'id' => $latestAttempt->id,
                    'status' => $latestAttempt->status->value,
                    'score' => $latestAttempt->score !== null ? (float) $latestAttempt->score : null,
                    'passed' => $latestAttempt->passed,
                    'started_at' => $latestAttempt->started_at?->toIso8601String(),
                    'submitted_at' => $latestAttempt->submitted_at?->toIso8601String(),
                ] : null);
            }
        }

        return $enrollment;
    }

    private function resolveEnrollmentForLesson(User $user, Lesson $lesson, bool $requireActive): Enrollment
    {
        $lesson->loadMissing('module');

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $lesson->module->course_id)
            ->first();

        if (! $enrollment) {
            throw (new ModelNotFoundException)->setModel(Enrollment::class);
        }

        $this->expireEnrollmentIfPastDue($enrollment);

        if ($requireActive && $enrollment->status !== EnrollmentStatus::Active) {
            throw new \DomainException('Only active enrollments can update lesson progress.');
        }

        if (! $requireActive && in_array($enrollment->status, [EnrollmentStatus::Dropped, EnrollmentStatus::Expired], true)) {
            throw new \DomainException('This lesson is not available for the current enrollment status.');
        }

        return $enrollment;
    }

    private function expireEnrollmentIfPastDue(Enrollment $enrollment): void
    {
        if (
            $enrollment->status === EnrollmentStatus::Active
            && $enrollment->due_at !== null
            && $enrollment->due_at->isPast()
        ) {
            $enrollment->status = EnrollmentStatus::Expired;
            $enrollment->save();
            $enrollment->refresh();
        }
    }

    private function ensureLessonBelongsToEnrollment(Enrollment $enrollment, Lesson $lesson): void
    {
        $lesson->loadMissing('module');

        if ((int) $lesson->module->course_id !== (int) $enrollment->course_id) {
            throw new \DomainException('The selected lesson does not belong to this enrollment.');
        }
    }

    public function firstOrCreateProgressPublic(Enrollment $enrollment, Lesson $lesson): LessonProgress
    {
        return $this->firstOrCreateProgress($enrollment, $lesson);
    }

    private function firstOrCreateProgress(Enrollment $enrollment, Lesson $lesson): LessonProgress
    {
        return LessonProgress::query()->firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => LessonProgressStatus::NotStarted,
                'progress_percent' => 0,
                'time_spent_seconds' => 0,
            ],
        );
    }

    /** @return Collection<int, LessonProgress> */
    private function progressRecords(Enrollment $enrollment): Collection
    {
        if (! $enrollment->relationLoaded('lessonProgress')) {
            $enrollment->load('lessonProgress');
        }

        return $enrollment->lessonProgress;
    }

    private function totalLessons(Enrollment $enrollment): int
    {
        $course = $enrollment->course;

        if ($course && $course->relationLoaded('modules')) {
            return $course->modules->flatMap(fn ($module) => $module->lessons)->count();
        }

        return (int) $this->orderedLessonsQuery($enrollment->course_id)->count('lessons.id');
    }

    private function orderedLessonsQuery(int $courseId): Builder
    {
        return Lesson::query()
            ->select('lessons.id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $courseId)
            ->orderBy('modules.sort_order')
            ->orderBy('lessons.sort_order')
            ->orderBy('lessons.id');
    }

    private function primaryMediaFile(Lesson $lesson): ?MediaFile
    {
        $primaryResource = $lesson->resources
            ->first(fn ($resource) => $resource->media_file_id !== null && $resource->resource_type->value === 'primary');

        $fallbackResource = $lesson->resources
            ->first(fn ($resource) => $resource->media_file_id !== null);

        return $primaryResource?->mediaFile ?? $fallbackResource?->mediaFile;
    }
}
