<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = ! in_array('error', $checks, true);

        return response()->json(
            [
                'status' => $allHealthy ? 'ok' : 'degraded',
                ...$checks,
                'timestamp' => now()->toIso8601String(),
            ],
            $allHealthy ? 200 : 503,
        );
    }

    private function checkDatabase(): string
    {
        try {
            DB::selectOne('SELECT 1');
            return 'connected';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            Cache::store('redis')->put('_health', 1, 5);
            return 'connected';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkStorage(): string
    {
        try {
            $disk = (string) config('media.disk', 's3');
            Storage::disk($disk)->exists('_health_probe');
            return 'connected';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
