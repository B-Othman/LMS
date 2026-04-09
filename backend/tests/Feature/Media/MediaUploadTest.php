<?php

namespace Tests\Feature\Media;

use App\Enums\MediaVisibility;
use App\Jobs\DeleteMediaFileJob;
use App\Jobs\GenerateMediaThumbnailJob;
use App\Models\MediaFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class MediaUploadTest extends TestCase
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

    public function test_content_manager_can_upload_an_image_and_receive_media_metadata(): void
    {
        Bus::fake();

        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $manager = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($manager, 'content_manager');

        Sanctum::actingAs($manager);

        $file = UploadedFile::fake()->createWithContent('cover.png', $this->tinyPng());

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => $file,
            'visibility' => 'private',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'File uploaded successfully.')
            ->assertJsonPath('data.original_filename', 'cover.png')
            ->assertJsonPath('data.mime_type', 'image/png')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.metadata.extension', 'png')
            ->assertJsonPath('data.metadata.dimensions.width', 1)
            ->assertJsonPath('data.metadata.dimensions.height', 1);

        $mediaFile = MediaFile::query()->firstOrFail();

        $this->assertSame($tenant->id, $mediaFile->tenant_id);
        $this->assertSame($manager->id, $mediaFile->uploaded_by);
        $this->assertTrue(Storage::disk('s3')->exists($mediaFile->path));
        $this->assertStringStartsWith('https://signed.example.test/', $response->json('data.url'));

        Bus::assertDispatched(GenerateMediaThumbnailJob::class, function (GenerateMediaThumbnailJob $job) use ($mediaFile) {
            return $job->mediaFileId === $mediaFile->id;
        });
    }

    public function test_upload_rejects_file_when_magic_bytes_do_not_match_an_allowed_media_type(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $manager = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($manager, 'content_manager');

        Sanctum::actingAs($manager);

        $file = UploadedFile::fake()->createWithContent(
            'payload.mp4',
            "MZ".str_repeat("\0", 256),
        );

        $response = $this->postJson('/api/v1/media/upload', [
            'file' => $file,
            'visibility' => 'private',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonPath('errors.0.field', 'file');

        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_authorized_user_can_fetch_private_media_metadata_and_download_url(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, 'content_manager');

        Storage::disk('s3')->put('tenants/'.$tenant->id.'/media/2026/04/guide.pdf', '%PDF-1.4');

        $mediaFile = MediaFile::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by' => $user->id,
            'disk' => 's3',
            'path' => 'tenants/'.$tenant->id.'/media/2026/04/guide.pdf',
            'original_filename' => 'guide.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
            'visibility' => MediaVisibility::PrivateAccess,
            'metadata' => ['extension' => 'pdf'],
        ]);

        Sanctum::actingAs($user);

        $metadataResponse = $this->getJson("/api/v1/media/{$mediaFile->id}");
        $downloadResponse = $this->getJson("/api/v1/media/{$mediaFile->id}/download");

        $metadataResponse->assertOk()
            ->assertJsonPath('data.id', $mediaFile->id)
            ->assertJsonPath('data.original_filename', 'guide.pdf');

        $this->assertStringStartsWith(
            'https://signed.example.test/',
            $metadataResponse->json('data.url'),
        );

        $downloadResponse->assertOk()
            ->assertJsonStructure(['data' => ['url', 'expires_at']]);

        $this->assertStringStartsWith(
            'https://signed.example.test/',
            $downloadResponse->json('data.url'),
        );
    }

    public function test_public_media_download_redirects_to_the_public_object_url(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($user, 'content_manager');

        $path = 'tenants/'.$tenant->id.'/media/2026/04/public-guide.pdf';
        Storage::disk('s3')->put($path, '%PDF-1.4');

        $mediaFile = MediaFile::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by' => $user->id,
            'disk' => 's3',
            'path' => $path,
            'original_filename' => 'public-guide.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
            'visibility' => MediaVisibility::PublicAccess,
            'metadata' => ['extension' => 'pdf'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->get("/api/v1/media/{$mediaFile->id}/download");

        $response->assertRedirect(Storage::disk('s3')->url($path));
    }

    public function test_content_manager_can_soft_delete_media_and_queue_storage_cleanup(): void
    {
        Bus::fake();

        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $manager = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($manager, 'content_manager');

        $path = 'tenants/'.$tenant->id.'/media/2026/04/deck.pdf';
        $thumbnailPath = 'tenants/'.$tenant->id.'/media/2026/04/thumbnails/deck-thumb.jpg';

        Storage::disk('s3')->put($path, '%PDF-1.4');
        Storage::disk('s3')->put($thumbnailPath, 'thumbnail');

        $mediaFile = MediaFile::query()->create([
            'tenant_id' => $tenant->id,
            'uploaded_by' => $manager->id,
            'disk' => 's3',
            'path' => $path,
            'original_filename' => 'deck.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
            'visibility' => MediaVisibility::PrivateAccess,
            'metadata' => [
                'extension' => 'pdf',
                'thumbnail_path' => $thumbnailPath,
            ],
        ]);

        Sanctum::actingAs($manager);

        $response = $this->deleteJson("/api/v1/media/{$mediaFile->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Media file deleted successfully.');

        $this->assertSoftDeleted('media_files', ['id' => $mediaFile->id]);

        Bus::assertDispatched(DeleteMediaFileJob::class, function (DeleteMediaFileJob $job) use ($path, $thumbnailPath) {
            return $job->disk === 's3'
                && $job->path === $path
                && $job->thumbnailPath === $thumbnailPath;
        });
    }

    private function tinyPng(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn8nZkAAAAASUVORK5CYII=',
            true,
        );
    }
}
