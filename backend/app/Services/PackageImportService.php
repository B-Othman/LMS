<?php

namespace App\Services;

use App\Enums\LessonType;
use App\Enums\PackageStatus;
use App\Models\ContentPackage;
use App\Models\ContentPackageVersion;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PackageImportService
{
    /**
     * Publish a validated package: create module/lessons from its SCOs.
     */
    public function publish(ContentPackage $package): ContentPackage
    {
        if ($package->status !== PackageStatus::Valid) {
            throw new RuntimeException('Only valid packages can be published.');
        }

        $version = $package->latestVersion();

        if (! $version) {
            throw new RuntimeException('No parsed version found for package.');
        }

        return DB::transaction(function () use ($package, $version): ContentPackage {
            $course = $package->course;

            $module = $this->resolveTargetModule($course, $package->title);
            $this->createLessonsForVersion($version, $module);

            $package->status = PackageStatus::Published;
            $package->save();

            return $package->fresh() ?? $package;
        });
    }

    private function resolveTargetModule(Course $course, string $packageTitle): Module
    {
        $course->loadMissing('modules');

        if ($course->modules->isNotEmpty()) {
            return $course->modules->first();
        }

        // No modules yet — create a default one named after the package
        return Module::query()->create([
            'course_id' => $course->id,
            'title' => $packageTitle,
            'sort_order' => 1,
        ]);
    }

    private function createLessonsForVersion(ContentPackageVersion $version, Module $module): void
    {
        $scos = $version->manifest_data['scos'] ?? [];

        if (empty($scos)) {
            throw new RuntimeException('Manifest contains no SCO items.');
        }

        $existingCount = $module->lessons()->count();

        foreach (array_values($scos) as $index => $sco) {
            $title = $sco['title'] ?? ('SCO ' . ($index + 1));

            Lesson::query()->create([
                'module_id' => $module->id,
                'title' => $title,
                'type' => LessonType::Scorm,
                'content_json' => [
                    'package_version_id' => $version->id,
                    'sco_identifier' => $sco['identifier'] ?? '',
                    'launch_path' => $sco['href'] ?? $version->launch_path,
                ],
                'sort_order' => $existingCount + $index + 1,
                'is_previewable' => false,
            ]);
        }
    }
}
