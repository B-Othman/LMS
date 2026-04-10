<?php

namespace Tests\Feature\Enrollments;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_admin_can_enroll_a_learner_into_a_published_course(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'admin@example.com']);
        $this->assignRole($admin, 'tenant_admin');

        $learner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'learner@example.com']);
        $this->assignRole($learner, 'learner');

        $course = $this->createPublishedCourse($tenant, $admin, 'Security Awareness 101');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'due_at' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Enrollment created successfully.')
            ->assertJsonPath('data.user_id', $learner->id)
            ->assertJsonPath('data.course_id', $course->id)
            ->assertJsonPath('data.status', EnrollmentStatus::Active->value);

        $this->assertDatabaseHas('enrollments', [
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'status' => EnrollmentStatus::Active->value,
        ]);

        // Notification delivery is handled by NotificationService (template-driven);
        // verified in dedicated notification tests.
    }

    public function test_admin_can_batch_enroll_and_receive_partial_failure_summary(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $firstLearner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'first@example.com']);
        $secondLearner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'second@example.com']);
        $this->assignRole($firstLearner, 'learner');
        $this->assignRole($secondLearner, 'learner');

        $course = $this->createPublishedCourse($tenant, $admin, 'Policy Foundations');

        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $secondLearner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now(),
            'status' => EnrollmentStatus::Active,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/enrollments', [
            'user_ids' => [$firstLearner->id, $secondLearner->id],
            'course_id' => $course->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Batch enrollment processed.')
            ->assertJsonPath('data.success_count', 1)
            ->assertJsonPath('data.failure_count', 1)
            ->assertJsonPath('data.failures.0.user_id', $secondLearner->id);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $firstLearner->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_admin_can_list_view_and_drop_enrollments_but_not_completed_ones(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $learner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Amina',
            'last_name' => 'Rahal',
            'email' => 'amina@example.com',
        ]);
        $this->assignRole($learner, 'learner');

        $activeCourse = $this->createPublishedCourse($tenant, $admin, 'Endpoint Security');
        $completedCourse = $this->createPublishedCourse($tenant, $admin, 'Email Security');

        $activeEnrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $activeCourse->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDays(2),
            'status' => EnrollmentStatus::Active,
        ]);

        $completedEnrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $completedCourse->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDays(10),
            'completed_at' => now()->subDay(),
            'status' => EnrollmentStatus::Completed,
        ]);

        Sanctum::actingAs($admin);

        $listResponse = $this->getJson('/api/v1/enrollments?status=active&search=amina');
        $detailResponse = $this->getJson("/api/v1/enrollments/{$activeEnrollment->id}");
        $dropResponse = $this->deleteJson("/api/v1/enrollments/{$activeEnrollment->id}");
        $completedDropResponse = $this->deleteJson("/api/v1/enrollments/{$completedEnrollment->id}");

        $listResponse->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $activeEnrollment->id)
            ->assertJsonPath('data.0.user.email', 'amina@example.com');

        $detailResponse->assertOk()
            ->assertJsonPath('data.progress_summary.total_lessons', 2)
            ->assertJsonPath('data.progress_percentage', 0);

        $dropResponse->assertOk()
            ->assertJsonPath('message', 'Enrollment dropped successfully.')
            ->assertJsonPath('data.status', EnrollmentStatus::Dropped->value);

        $completedDropResponse->assertStatus(422)
            ->assertJsonPath('message', 'Completed or already dropped enrollments cannot be removed.');

        $this->assertDatabaseHas('enrollments', [
            'id' => $activeEnrollment->id,
            'status' => EnrollmentStatus::Dropped->value,
        ]);
    }

    public function test_learner_can_view_own_course_list_and_detail(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');

        $learner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'learner@example.com']);
        $otherLearner = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'other@example.com']);
        $this->assignRole($learner, 'learner');
        $this->assignRole($otherLearner, 'learner');

        $activeCourse = $this->createPublishedCourse($tenant, $admin, 'Threat Modeling Basics');
        $completedCourse = $this->createPublishedCourse($tenant, $admin, 'Secure Coding Essentials');
        $otherCourse = $this->createPublishedCourse($tenant, $admin, 'Network Defense');

        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $activeCourse->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDays(4),
            'due_at' => now()->addDays(7),
            'status' => EnrollmentStatus::Active,
        ]);

        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $completedCourse->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDays(20),
            'completed_at' => now()->subDays(2),
            'status' => EnrollmentStatus::Completed,
        ]);

        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $otherLearner->id,
            'course_id' => $otherCourse->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDays(1),
            'status' => EnrollmentStatus::Active,
        ]);

        Sanctum::actingAs($learner);

        $listResponse = $this->getJson('/api/v1/my/courses?sort_by=progress&sort_dir=desc');
        $detailResponse = $this->getJson("/api/v1/my/courses/{$activeCourse->id}");
        $ownEnrollmentResponse = $this->getJson('/api/v1/enrollments');

        $listResponse->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.course.id', $completedCourse->id)
            ->assertJsonPath('data.0.progress_percentage', 100);

        $detailResponse->assertOk()
            ->assertJsonPath('data.course.id', $activeCourse->id)
            ->assertJsonPath('data.enrollment.status', EnrollmentStatus::Active->value)
            ->assertJsonPath('data.enrollment.progress_summary.total_lessons', 2)
            ->assertJsonPath('data.course.modules.0.lessons.0.progress.status', 'not_started');

        $ownEnrollmentResponse->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    private function createPublishedCourse(Tenant $tenant, User $creator, string $title): Course
    {
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'status' => CourseStatus::Published,
            'visibility' => 'private',
            'created_by' => $creator->id,
        ]);

        $module = Module::query()->create([
            'course_id' => $course->id,
            'title' => "{$title} Module",
            'sort_order' => 1,
        ]);

        Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Lesson One',
            'type' => LessonType::Text,
            'sort_order' => 1,
        ]);

        Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Lesson Two',
            'type' => LessonType::Video,
            'sort_order' => 2,
        ]);

        return $course;
    }
}
