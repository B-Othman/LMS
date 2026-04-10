<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    private const RESULT_LIMIT = 10;

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['nullable', 'string', 'in:users,courses,all'],
        ]);

        $query = trim($data['q']);
        $type = $data['type'] ?? 'all';
        $tenantId = $request->user()->tenant_id;

        $results = ['users' => [], 'courses' => []];

        if ($type === 'users' || $type === 'all') {
            $results['users'] = $this->searchUsers($tenantId, $query);
        }

        if ($type === 'courses' || $type === 'all') {
            $results['courses'] = $this->searchCourses($tenantId, $query);
        }

        return $this->success($results);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchUsers(int $tenantId, string $query): array
    {
        $tsQuery = $this->sanitizeForTsquery($query);

        return DB::table('users')
            ->select(['id', 'first_name', 'last_name', 'email', 'status'])
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereRaw(
                "to_tsvector('english', coalesce(first_name,'') || ' ' || coalesce(last_name,'') || ' ' || coalesce(email,'')) @@ plainto_tsquery('english', ?)",
                [$query],
            )
            ->orderByRaw(
                "ts_rank(to_tsvector('english', coalesce(first_name,'') || ' ' || coalesce(last_name,'') || ' ' || coalesce(email,'')), plainto_tsquery('english', ?)) DESC",
                [$query],
            )
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => trim("{$row->first_name} {$row->last_name}"),
                'email' => $row->email,
                'status' => $row->status,
                'type' => 'user',
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchCourses(int $tenantId, string $query): array
    {
        return DB::table('courses')
            ->select(['id', 'title', 'status', 'short_description', 'category_id'])
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereRaw(
                "to_tsvector('english', coalesce(title,'') || ' ' || coalesce(description,'') || ' ' || coalesce(short_description,'')) @@ plainto_tsquery('english', ?)",
                [$query],
            )
            ->orderByRaw(
                "ts_rank(to_tsvector('english', coalesce(title,'') || ' ' || coalesce(description,'') || ' ' || coalesce(short_description,'')), plainto_tsquery('english', ?)) DESC",
                [$query],
            )
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'status' => $row->status,
                'short_description' => $row->short_description,
                'type' => 'course',
            ])
            ->values()
            ->all();
    }

    private function sanitizeForTsquery(string $query): string
    {
        // Remove characters that could break plainto_tsquery
        return preg_replace('/[^a-zA-Z0-9\s\-_@.]/', ' ', $query) ?? $query;
    }
}
