<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', 'string', 'max:50'],
            'entity_id' => ['nullable', 'integer'],
            'actor_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantId = $request->user()->tenant_id;

        $query = ActivityLog::with('actor')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (! empty($filters['entity_id'])) {
            $query->where('entity_id', (int) $filters['entity_id']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_user_id', (int) $filters['actor_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim((string) $filters['search']).'%';
            $query->where('description', 'ILIKE', $search);
        }

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 20));

        return $this->success(
            AuditLogResource::collection($paginator->getCollection())->resolve(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $log = ActivityLog::with('actor')
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return $this->success(new AuditLogResource($log));
    }

    public function userTrail(int $userId, Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $paginator = ActivityLog::with('actor')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($userId) {
                $q->where('actor_user_id', $userId)
                    ->orWhere(function ($q2) use ($userId) {
                        $q2->where('entity_type', 'user')
                            ->where('entity_id', $userId);
                    });
            })
            ->orderByDesc('created_at')
            ->paginate((int) ($request->query('per_page', 20)));

        return $this->success(
            AuditLogResource::collection($paginator->getCollection())->resolve(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }
}
