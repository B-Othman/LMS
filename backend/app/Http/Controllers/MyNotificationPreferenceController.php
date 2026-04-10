<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MyNotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $preferences = $this->notificationService->getUserPreferences($request->user()->id);

        return $this->success($preferences);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.type' => ['required', 'string'],
            'preferences.*.email_enabled' => ['required', 'boolean'],
            'preferences.*.in_app_enabled' => ['required', 'boolean'],
        ]);

        $this->notificationService->updateUserPreferences(
            $request->user()->id,
            $data['preferences'],
        );

        $preferences = $this->notificationService->getUserPreferences($request->user()->id);

        return $this->success($preferences, 'Preferences updated.');
    }
}
