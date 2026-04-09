<?php

namespace Tests\Feature\Progress;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Enums\MediaVisibility;
use App\Enums\ResourceType;
use App\Events\CourseCompleted;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\MediaFile;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'media.disk' => 's3',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
        ]);

        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path, \DateTimeInterface $expiration, array $options = []) => 'https://signed.example.test/'.ltrim($path, '/'),
        );
    }

    public function test_learner_can_start_complete_and_track_course_progress(): void
    {
        Event::fake([CourseCompleted::class]);

        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');
        $this->assignRole($learner, 'learner');

        [$course, $lessons] = $this->createPublishedCourse($tenant, $admin);

        $enrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDay(),
            'status' => EnrollmentStatus::Active,
            'progress_percent' => 0,
            'completed_lessons_count' => 0,
        ]);

        Sanctum::actingAs($learner);

        $startResponse = $this->postJson("/api/v1/my/lessons/{$lessons['text']->id}/start");
        $heartbeatResponse = $this->postJson("/api/v1/my/lessons/{$lessons['text']->id}/heartbeat", [
            'seconds' => 30,
        ]);
        $completeFirstResponse = $this->postJson("/api/v1/my/lessons/{$lessons['text']->id}/complete");
        $completeSecondResponse = $this->postJson("/api/v1/my/lessons/{$lessons['document']->id}/complete");
        $completeThirdResponse = $this->postJson("/api/v1/my/lessons/{$lessons['video']->id}/complete");

        $startResponse->assertOk()
            ->assertJsonPath('message', 'Lesson started successfully.')
            ->assertJsonPath('data.course.modules.0.lessons.0.progress.status', 'in_progress');

        $heartbeatResponse->assertOk()
            ->assertJsonPath('data.lesson_id', $lessons['text']->id)
            ->assertJsonPath('data.time_spent_seconds', 30);

        $completeFirstResponse->assertOk()
            ->assertJsonPath('data.enrollment.progress_percentage', 33)
            ->assertJsonPath('data.enrollment.completed_lessons_count', 1)
            ->assertJsonPath('data.course.modules.0.lessons.0.progress.status', 'completed');

        $completeSecondResponse->assertOk()
            ->assertJsonPath('data.enrollment.progress_percentage', 67)
            ->assertJsonPath('data.enrollment.completed_lessons_count', 2);

        $completeThirdResponse->assertOk()
            ->assertJsonPath('data.enrollment.status', EnrollmentStatus::Completed->value)
            ->assertJsonPath('data.enrollment.progress_percentage', 100)
            ->assertJsonPath('data.enrollment.completed_lessons_count', 3);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lessons['text']->id,
            'status' => 'completed',
            'time_spent_seconds' => 30,
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lessons['document']->id,
            'status' => 'completed',
            'progress_percent' => 100,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Completed->value,
            'progress_percent' => 100,
            'completed_lessons_count' => 3,
        ]);

        Event::assertDispatched(CourseCompleted::class, function (CourseCompleted $event) use ($enrollment) {
            return $event->enrollment->id === $enrollment->id;
        });
    }

    public function test_learner_can_fetch_lesson_content_and_progress_tree(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');
        $this->assignRole($learner, 'learner');

        [$course, $lessons] = $this->createPublishedCourse($tenant, $admin, true);

        $enrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDays(2),
            'status' => EnrollmentStatus::Active,
            'progress_percent' => 0,
            'completed_lessons_count' => 0,
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/v1/my/lessons/{$lessons['text']->id}/start")->assertOk();

        $textContentResponse = $this->getJson("/api/v1/lessons/{$lessons['text']->id}/content");
        $videoContentResponse = $this->getJson("/api/v1/lessons/{$lessons['video']->id}/content");
        $documentContentResponse = $this->getJson("/api/v1/lessons/{$lessons['document']->id}/content");
        $progressResponse = $this->getJson("/api/v1/my/enrollments/{$enrollment->id}/progress");

        $textContentResponse->assertOk()
            ->assertJsonPath('data.id', $lessons['text']->id)
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.content_html', '<p>Read this carefully.</p>');

        $videoContentResponse->assertOk()
            ->assertJsonPath('data.id', $lessons['video']->id)
            ->assertJsonPath('data.type', 'video')
            ->assertJsonPath('data.mime_type', 'video/mp4');

        $this->assertStringStartsWith(
            'https://signed.example.test/',
            (string) $videoContentResponse->json('data.content_url'),
        );

        $documentContentResponse->assertOk()
            ->assertJsonPath('data.id', $lessons['document']->id)
            ->assertJsonPath('data.type', 'document')
            ->assertJsonPath('data.mime_type', 'application/pdf');

        $this->assertStringStartsWith(
            'https://signed.example.test/',
            (string) $documentContentResponse->json('data.content_url'),
        );

        $progressResponse->assertOk()
            ->assertJsonPath('data.enrollment.id', $enrollment->id)
            ->assertJsonPath('data.enrollment.next_lesson_id', $lessons['text']->id)
            ->assertJsonPath('data.course.modules.0.lessons.0.progress.status', 'in_progress')
            ->assertJsonPath('data.course.modules.0.lessons.1.progress.status', 'not_started');
    }

    /**
     * @return array{0: Course, 1: array{text: Lesson, video: Lesson, document: Lesson}}
     */
    private function createPublishedCourse(Tenant $tenant, User $creator, bool $withMedia = false): array
    {
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Learner Progress Course',
            'slug' => 'learner-progress-course',
            'status' => CourseStatus::Published,
            'visibility' => 'private',
            'created_by' => $creator->id,
            'description' => 'Course used for learner progress tests.',
        ]);

        $module = Module::query()->create([
            'course_id' => $course->id,
            'title' => 'Main Module',
            'sort_order' => 1,
        ]);

        $textLesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Text Lesson',
            'type' => LessonType::Text,
            'content_html' => '<p>Read this carefully.</p>',
            'sort_order' => 1,
        ]);

        $videoLesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Video Lesson',
            'type' => LessonType::Video,
            'duration_minutes' => 12,
            'sort_order' => 2,
        ]);

        $documentLesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Document Lesson',
            'type' => LessonType::Document,
            'duration_minutes' => 8,
            'sort_order' => 3,
        ]);

        if ($withMedia) {
            $this->attachMediaResource($tenant, $creator, $videoLesson, 'video/mp4', 'video.mp4');
            $this->attachMediaResource($tenant, $creator, $documentLesson, 'application/pdf', 'guide.pdf');
        }

        return [$course, [
            'text' => $textLesson,
            'video' => $videoLesson,
            'document' => $documentLesson,
        ]];
    }

    private function attachMediaResource(
        Tenant $tenant,
        User $uploader,
        Lesson $lesson,
        string $mimeType,
        string $filename,
    ): void {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $path = 'tenants/'.$tenant->id.'/media/2026/04/'.uniqid('lesson-', true).'.'.$extension;

        Storage::disk('s3')->put($path, 'payload');

        $mediaFile = MediaFile::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by' => $uploader->id,
            'disk' => 's3',
            'path' => $path,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'size_bytes' => 7,
            'visibility' => MediaVisibility::PrivateAccess,
            'metadata' => [
                'extension' => $extension,
                'duration_minutes' => $mimeType === 'video/mp4' ? 12 : null,
            ],
        ]);

        LessonResource::query()->create([
            'lesson_id' => $lesson->id,
            'media_file_id' => $mediaFile->id,
            'label' => $filename,
            'resource_type' => ResourceType::Primary,
            'sort_order' => 1,
        ]);
    }
}
