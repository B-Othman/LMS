<?php

namespace App\Http\Controllers;

use App\Enums\LaunchSessionStatus;
use App\Models\ContentPackageVersion;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\PackageLaunchSession;
use App\Services\ScormRuntimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ScormPlayerController extends Controller
{
    public function __construct(
        private readonly ScormRuntimeService $runtime,
    ) {}

    /**
     * POST /packages/{packageVersionId}/launch
     * Create a launch session. Returns { session_id, launch_url }.
     */
    public function launch(Request $request, int $packageVersionId): JsonResponse
    {
        $version = ContentPackageVersion::query()->findOrFail($packageVersionId);
        $user = $request->user();

        // Resolve enrollment for the lesson linked to this package version
        $lesson = Lesson::query()
            ->whereJsonContains('content_json->package_version_id', $packageVersionId)
            ->first();

        if (! $lesson) {
            return $this->error('No lesson found for this package version.', 404);
        }

        $lesson->loadMissing('module');

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $lesson->module->course_id)
            ->firstOrFail();

        $result = $this->runtime->launch($version, $enrollment, $user, $lesson);

        return $this->success([
            'session_id' => $result['session']->id,
            'launch_url' => $result['launch_url'],
        ]);
    }

    /**
     * GET /scorm/player/{sessionId}
     * Serves the HTML player page.
     */
    public function player(Request $request, int $sessionId): Response
    {
        $session = PackageLaunchSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->where('status', LaunchSessionStatus::Active->value)
            ->with('packageVersion')
            ->firstOrFail();

        $version = $session->packageVersion;
        $scoUrl = $this->resolveScoUrl($version->launch_path);

        $html = view('scorm.player', [
            'session' => $session,
            'sco_url' => $scoUrl,
            'api_base' => rtrim(config('app.url'), '/') . '/api/v1',
            'session_token' => $request->bearerToken() ?? '',
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'self' blob: data:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; frame-src *; img-src * data: blob:;",
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * POST /scorm/sessions/{sessionId}/commit
     * Save cmi_data snapshot (LMSCommit).
     */
    public function commit(Request $request, int $sessionId): JsonResponse
    {
        $session = $this->resolveOwnedActiveSession($request, $sessionId);
        $cmiData = (array) $request->input('cmi_data', []);

        $this->runtime->commit($session, $cmiData);

        return $this->success(message: 'Committed.');
    }

    /**
     * POST /scorm/sessions/{sessionId}/finish
     * Close the session (LMSFinish).
     */
    public function finish(Request $request, int $sessionId): JsonResponse
    {
        $session = $this->resolveOwnedActiveSession($request, $sessionId);
        $cmiData = (array) $request->input('cmi_data', []);

        $this->runtime->finish($session, $cmiData);

        return $this->success(message: 'Session closed.');
    }

    /**
     * GET /scorm/assets/{sessionId}/{path}
     * Serve extracted SCORM asset via signed URL redirect.
     */
    public function asset(Request $request, int $sessionId, string $path): \Symfony\Component\HttpFoundation\Response
    {
        $session = PackageLaunchSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->with('packageVersion')
            ->firstOrFail();

        // Prevent path traversal
        $safePath = ltrim(str_replace(['../', '.\\', '../'], '', $path), '/');

        $storagePath = $session->packageVersion->extracted_path . '/' . $safePath;
        $disk = (string) config('media.disk', 's3');

        if (! Storage::disk($disk)->exists($storagePath)) {
            abort(404);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk($disk);
        $url = $storage->temporaryUrl(
            $storagePath,
            now()->addMinutes(15),
        );

        return redirect($url);
    }

    /**
     * GET /scorm/sessions/{sessionId}/state
     * Return current cmi_data for the player to seed the RTE.
     */
    public function state(Request $request, int $sessionId): JsonResponse
    {
        $session = PackageLaunchSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->with('runtimeState')
            ->firstOrFail();

        return $this->success([
            'cmi_data' => $session->runtimeState?->cmi_data ?? [],
        ]);
    }

    private function resolveOwnedActiveSession(Request $request, int $sessionId): PackageLaunchSession
    {
        return PackageLaunchSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->where('status', LaunchSessionStatus::Active->value)
            ->firstOrFail();
    }

    private function resolveScoUrl(string $launchPath): string
    {
        // Return the relative launch path — the asset proxy route appends it.
        return $launchPath;
    }
}
