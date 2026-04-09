<?php

namespace Tests\Feature\Certificates;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Events\CourseCompleted;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CertificateManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'certificates.disk' => 's3',
            'certificates.background_disk' => 's3',
        ]);

        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path) => 'https://signed.example.test/'.ltrim($path, '/'),
        );
    }

    public function test_admin_can_manage_templates_and_issue_certificates_automatically(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');
        $this->assignRole($learner, 'learner');

        Sanctum::actingAs($admin);

        $createTemplateResponse = $this->post('/api/v1/certificate-templates', [
            'name' => 'Default Completion Certificate',
            'description' => 'Issued when learners complete a course.',
            'layout' => 'landscape',
            'background_image' => UploadedFile::fake()->image('certificate-bg.png', 1600, 1200),
            'content_html' => <<<HTML
<div style="padding: 72px; text-align: center;">
  <p style="letter-spacing: 0.32em; text-transform: uppercase; color: #5b92c6;">Securecy</p>
  <h1 style="font-size: 42px; margin-top: 24px;">Certificate of Completion</h1>
  <p style="font-size: 18px; margin-top: 30px;">This certifies that</p>
  <h2 style="font-size: 34px; margin-top: 18px;">{{ learner_name }}</h2>
  <p style="font-size: 18px; margin-top: 26px;">has successfully completed</p>
  <h3 style="font-size: 28px; margin-top: 18px;">{{ course_title }}</h3>
  <p style="font-size: 16px; margin-top: 24px;">Completed on {{ completion_date }}</p>
  <p style="font-size: 14px; margin-top: 32px;">Certificate ID: {{ certificate_id }}</p>
  <p style="font-size: 14px;">Verification Code: {{ verification_code }}</p>
</div>
HTML,
            'is_default' => '1',
            'status' => 'active',
        ], ['Accept' => 'application/json']);

        $createTemplateResponse->assertCreated()
            ->assertJsonPath('data.name', 'Default Completion Certificate')
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.status', 'active');

        $templateId = (int) $createTemplateResponse->json('data.id');

        $this->get("/api/v1/certificate-templates/{$templateId}/preview", ['Accept' => 'application/json'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        [$course, $enrollment] = $this->createCompletedEnrollment($tenant, $admin, $learner);

        event(new CourseCompleted($enrollment->loadMissing('user', 'course')));

        $certificate = Certificate::query()->first();

        $this->assertNotNull($certificate);
        $this->assertNotNull($certificate?->file_path);
        Storage::disk('s3')->assertExists((string) $certificate?->file_path);

        $listTemplatesResponse = $this->getJson('/api/v1/certificate-templates');
        $listCertificatesResponse = $this->getJson('/api/v1/certificates');
        $downloadResponse = $this->getJson("/api/v1/certificates/{$certificate->id}/download");

        $listTemplatesResponse->assertOk()
            ->assertJsonPath('data.0.issued_count', 1);
        $listCertificatesResponse->assertOk()
            ->assertJsonPath('data.0.id', $certificate->id)
            ->assertJsonPath('data.0.status', 'active');
        $downloadResponse->assertOk();

        $this->assertStringStartsWith(
            'https://signed.example.test/',
            (string) $downloadResponse->json('data.url'),
        );

        $deleteTemplateResponse = $this->deleteJson("/api/v1/certificate-templates/{$templateId}");
        $deleteTemplateResponse->assertStatus(422);

        Sanctum::actingAs($learner);

        $myCertificatesResponse = $this->getJson('/api/v1/my/certificates');
        $myDownloadResponse = $this->getJson("/api/v1/my/certificates/{$certificate->id}/download");

        $myCertificatesResponse->assertOk()
            ->assertJsonPath('data.0.id', $certificate->id);
        $myDownloadResponse->assertOk();

        $this->assertStringStartsWith(
            'https://signed.example.test/',
            (string) $myDownloadResponse->json('data.url'),
        );

        app('auth')->forgetGuards();

        $verifyResponse = $this->getJson("/api/v1/certificates/verify/{$certificate->verification_code}");
        $verifyResponse->assertOk()
            ->assertJsonPath('data.status', 'valid')
            ->assertJsonPath('data.course_title', $course->title)
            ->assertJsonPath('data.learner_name', $learner->full_name);

        Sanctum::actingAs($admin);

        $revokeResponse = $this->postJson("/api/v1/certificates/{$certificate->id}/revoke", [
            'reason' => 'Issued for a retired course revision.',
        ]);

        $revokeResponse->assertOk()
            ->assertJsonPath('data.status', 'revoked')
            ->assertJsonPath('data.revoked_reason', 'Issued for a retired course revision.');

        $verifyAfterRevokeResponse = $this->getJson("/api/v1/certificates/verify/{$certificate->verification_code}");
        $verifyAfterRevokeResponse->assertOk()
            ->assertJsonPath('data.status', 'revoked');
    }

    /**
     * @return array{0: Course, 1: Enrollment}
     */
    private function createCompletedEnrollment(Tenant $tenant, User $admin, User $learner): array
    {
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Secure Operations',
            'slug' => 'secure-operations',
            'status' => CourseStatus::Published,
            'visibility' => 'private',
            'created_by' => $admin->id,
        ]);

        $module = Module::query()->create([
            'course_id' => $course->id,
            'title' => 'Foundations',
            'sort_order' => 1,
        ]);

        Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Intro Lesson',
            'type' => LessonType::Text,
            'content_html' => '<p>Complete this course.</p>',
            'sort_order' => 1,
        ]);

        $enrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subDay(),
            'completed_at' => now(),
            'status' => EnrollmentStatus::Completed,
            'progress_percent' => 100,
            'completed_lessons_count' => 1,
        ]);

        return [$course, $enrollment];
    }
}
