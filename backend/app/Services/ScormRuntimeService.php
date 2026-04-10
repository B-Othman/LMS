<?php

namespace App\Services;

use App\Enums\LaunchSessionStatus;
use App\Enums\LessonProgressStatus;
use App\Models\ContentPackageVersion;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\PackageLaunchSession;
use App\Models\ScormRuntimeState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScormRuntimeService
{
    public function __construct(
        private readonly ProgressService $progress,
    ) {}

    /**
     * Create a new launch session and return the session + launch URL.
     *
     * @return array{session: PackageLaunchSession, launch_url: string}
     */
    public function launch(
        ContentPackageVersion $version,
        Enrollment $enrollment,
        User $user,
        Lesson $lesson,
    ): array {
        $previousState = $this->lastCompletedCmiData($version, $enrollment);

        $session = DB::transaction(function () use ($version, $enrollment, $user): PackageLaunchSession {
            $session = PackageLaunchSession::query()->create([
                'package_version_id' => $version->id,
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'status' => LaunchSessionStatus::Active,
            ]);

            $entryStatus = $previousState ? 'resume' : 'ab-initio';
            $lessonStatus = $previousState['cmi.core.lesson_status'] ?? 'not attempted';

            $initialCmi = $this->buildInitialCmi($user, $lessonStatus, $entryStatus, $previousState);

            ScormRuntimeState::query()->create([
                'launch_session_id' => $session->id,
                'cmi_data' => $initialCmi,
            ]);

            return $session;
        });

        $launchUrl = route('scorm.player', ['sessionId' => $session->id]);

        return ['session' => $session, 'launch_url' => $launchUrl];
    }

    /**
     * Persist a cmi_data snapshot from LMSCommit.
     *
     * @param  array<string, mixed>  $cmiData
     */
    public function commit(PackageLaunchSession $session, array $cmiData): void
    {
        if ($session->status !== LaunchSessionStatus::Active) {
            throw new RuntimeException('Session is no longer active.');
        }

        $state = $session->runtimeState;

        if ($state) {
            $merged = array_merge($state->cmi_data ?? [], $cmiData);
            $state->cmi_data = $merged;
            $state->last_updated_at = now();
            $state->save();
        } else {
            ScormRuntimeState::query()->create([
                'launch_session_id' => $session->id,
                'cmi_data' => $cmiData,
            ]);
        }
    }

    /**
     * Close the session (LMSFinish) and map SCORM status to LMS progress.
     *
     * @param  array<string, mixed>  $cmiData
     */
    public function finish(PackageLaunchSession $session, array $cmiData): void
    {
        DB::transaction(function () use ($session, $cmiData): void {
            // Persist final cmi_data
            $this->commit($session, $cmiData);

            $now = now();
            $durationSeconds = (int) $now->diffInSeconds($session->launched_at);

            $session->status = LaunchSessionStatus::Completed;
            $session->closed_at = $now;
            $session->duration_seconds = $durationSeconds;
            $session->save();

            $this->applyProgressFromCmi($session, $cmiData);
        });
    }

    /**
     * Map cmi.core.lesson_status → LessonProgress status.
     *
     * @param  array<string, mixed>  $cmiData
     */
    private function applyProgressFromCmi(PackageLaunchSession $session, array $cmiData): void
    {
        $lessonStatus = strtolower((string) ($cmiData['cmi.core.lesson_status'] ?? 'incomplete'));
        $scoreRaw = isset($cmiData['cmi.core.score.raw'])
            ? (float) $cmiData['cmi.core.score.raw']
            : null;

        $session->load('enrollment');
        $enrollment = $session->enrollment;

        if (! $enrollment) {
            return;
        }

        // Find the lesson linked to this package version
        $lesson = Lesson::query()
            ->whereJsonContains('content_json->package_version_id', $session->package_version_id)
            ->first();

        if (! $lesson) {
            return;
        }

        $isCompleted = in_array($lessonStatus, ['completed', 'passed'], true);

        $progressRecord = DB::transaction(function () use ($enrollment, $lesson, $isCompleted, $lessonStatus, $scoreRaw): void {
            $progressData = $this->progress->firstOrCreateProgressPublic($enrollment, $lesson);

            if ($isCompleted) {
                $progressData->status = LessonProgressStatus::Completed;
                $progressData->completed_at ??= now();
                $progressData->progress_percent = 100;
            } elseif ($lessonStatus === 'failed') {
                $progressData->status = LessonProgressStatus::InProgress;
                $progressData->progress_percent = max((int) $progressData->progress_percent, 50);
            } else {
                // incomplete / browsed / not attempted
                if ($progressData->status !== LessonProgressStatus::Completed) {
                    $progressData->status = LessonProgressStatus::InProgress;
                }
            }

            $progressData->last_accessed_at = now();
            $progressData->save();

            if ($isCompleted) {
                $this->progress->recalculateCourseProgress($enrollment);
            }
        });
    }

    /** @return array<string, mixed>|null */
    private function lastCompletedCmiData(ContentPackageVersion $version, Enrollment $enrollment): ?array
    {
        $session = PackageLaunchSession::query()
            ->where('package_version_id', $version->id)
            ->where('enrollment_id', $enrollment->id)
            ->where('status', LaunchSessionStatus::Completed->value)
            ->latest('closed_at')
            ->first();

        if (! $session) {
            return null;
        }

        $session->loadMissing('runtimeState');

        return $session->runtimeState?->cmi_data;
    }

    /**
     * @param  array<string, mixed>|null  $resume
     * @return array<string, mixed>
     */
    private function buildInitialCmi(User $user, string $lessonStatus, string $entry, ?array $resume): array
    {
        $initial = [
            'cmi.core.student_id' => (string) $user->id,
            'cmi.core.student_name' => $this->formatStudentName($user),
            'cmi.core.lesson_status' => $lessonStatus,
            'cmi.core.entry' => $entry,
            'cmi.core.credit' => 'credit',
            'cmi.core.lesson_mode' => 'normal',
            'cmi.core.score.raw' => '',
            'cmi.core.score.min' => '',
            'cmi.core.score.max' => '',
            'cmi.core.session_time' => '0000:00:00.00',
            'cmi.core.total_time' => '0000:00:00.00',
            'cmi.suspend_data' => '',
            'cmi.launch_data' => '',
            'cmi.comments' => '',
            'cmi.comments_from_lms' => '',
        ];

        if ($resume) {
            // Carry over suspend_data and score for resume
            foreach (['cmi.suspend_data', 'cmi.core.score.raw', 'cmi.core.score.min', 'cmi.core.score.max', 'cmi.core.total_time'] as $key) {
                if (isset($resume[$key]) && $resume[$key] !== '') {
                    $initial[$key] = $resume[$key];
                }
            }
        }

        return $initial;
    }

    private function formatStudentName(User $user): string
    {
        $lastName = $user->last_name ?? '';
        $firstName = $user->first_name ?? $user->name ?? '';

        if ($lastName !== '') {
            return "{$lastName}, {$firstName}";
        }

        return $firstName;
    }
}
