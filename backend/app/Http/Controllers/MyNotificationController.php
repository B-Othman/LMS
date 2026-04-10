<?php

namespace App\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AppNotification::where('user_id', $user->id)
            ->inApp()
            ->orderByDesc('created_at');

        if ($request->query('filter') === 'unread') {
            $query->unread();
        }

        $paginator = $query->paginate((int) ($request->query('per_page', 20)));

        return $this->success(
            NotificationResource::collection($paginator->getCollection())->resolve(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public function markRead(int $id, Request $request): JsonResponse
    {
        $notification = AppNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($notification->status !== NotificationStatus::Read) {
            $notification->update([
                'status' => NotificationStatus::Read->value,
                'read_at' => now(),
            ]);
        }

        return $this->success(new NotificationResource($notification));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->inApp()
            ->unread()
            ->update([
                'status' => NotificationStatus::Read->value,
                'read_at' => now(),
            ]);

        return $this->success(null, 'All notifications marked as read.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->inApp()
            ->unread()
            ->count();

        return $this->success(['count' => $count]);
    }
}
