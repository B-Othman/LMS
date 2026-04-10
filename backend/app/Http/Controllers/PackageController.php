<?php

namespace App\Http\Controllers;

use App\Enums\PackageStandard;
use App\Enums\PackageStatus;
use App\Http\Requests\Packages\UploadPackageRequest;
use App\Jobs\ValidatePackageJob;
use App\Models\ContentPackage;
use App\Services\CourseService;
use App\Services\PackageImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly PackageImportService $importer,
    ) {}

    /**
     * POST /courses/{courseId}/packages
     * Upload a SCORM ZIP for a course.
     */
    public function upload(UploadPackageRequest $request, int $courseId): JsonResponse
    {
        $course = $this->courses->findCourse($courseId);
        $this->authorize('manageLessons', $course);

        $file = $request->file('file');
        $user = $request->user();

        $disk = (string) config('media.disk', 's3');
        $path = sprintf(
            'tenants/%d/packages/uploads/%s.zip',
            $course->tenant_id,
            (string) Str::uuid(),
        );

        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            return $this->error('Could not read uploaded file.', 422);
        }

        try {
            Storage::disk($disk)->put($path, $stream, [
                'visibility' => 'private',
                'ContentType' => 'application/zip',
            ]);
        } finally {
            fclose($stream);
        }

        $package = ContentPackage::query()->create([
            'tenant_id' => $course->tenant_id,
            'course_id' => $course->id,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'standard' => PackageStandard::Scorm12,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size_bytes' => (int) $file->getSize(),
            'status' => PackageStatus::Uploaded,
            'uploaded_by' => $user->id,
        ]);

        ValidatePackageJob::dispatch($package->id)->afterCommit();

        return $this->success($this->packageData($package), 'Package uploaded. Validation started.', 201);
    }

    /**
     * GET /packages/{id}
     * Package detail with status, errors, latest version.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $package = ContentPackage::query()
            ->with(['versions' => fn ($q) => $q->orderByDesc('version_number')->limit(1)])
            ->findOrFail($id);

        $course = $this->courses->findCourse($package->course_id);
        $this->authorize('view', $course);

        $latestVersion = $package->versions->first();

        return $this->success([
            ...$this->packageData($package),
            'version' => $latestVersion ? $this->versionData($latestVersion) : null,
        ]);
    }

    /**
     * POST /packages/{id}/publish
     * Admin confirms import — create lessons from SCOs.
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $package = ContentPackage::query()->findOrFail($id);
        $course = $this->courses->findCourse($package->course_id);
        $this->authorize('manageLessons', $course);

        $package = $this->importer->publish($package);

        return $this->success($this->packageData($package), 'Package published. Lessons created.');
    }

    /**
     * DELETE /packages/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $package = ContentPackage::query()->with('versions')->findOrFail($id);
        $course = $this->courses->findCourse($package->course_id);
        $this->authorize('manageLessons', $course);

        $disk = (string) config('media.disk', 's3');

        // Delete original ZIP
        Storage::disk($disk)->delete($package->file_path);

        // Delete extracted versions
        foreach ($package->versions as $version) {
            Storage::disk($disk)->deleteDirectory($version->extracted_path);
        }

        $package->delete();

        return $this->success(message: 'Package deleted.');
    }

    /** @return array<string, mixed> */
    private function packageData(ContentPackage $package): array
    {
        return [
            'id' => $package->id,
            'course_id' => $package->course_id,
            'title' => $package->title,
            'standard' => $package->standard->value,
            'original_filename' => $package->original_filename,
            'file_size_bytes' => $package->file_size_bytes,
            'status' => $package->status->value,
            'error_message' => $package->error_message,
            'created_at' => $package->created_at?->toIso8601String(),
            'updated_at' => $package->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function versionData(mixed $version): array
    {
        return [
            'id' => $version->id,
            'version_number' => $version->version_number,
            'launch_path' => $version->launch_path,
            'sco_count' => $version->sco_count,
            'metadata' => $version->metadata,
            'scos' => $version->manifest_data['scos'] ?? [],
            'created_at' => $version->created_at?->toIso8601String(),
        ];
    }
}
