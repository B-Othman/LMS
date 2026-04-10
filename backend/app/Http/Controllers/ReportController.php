<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $stats = $this->reporting->overviewStats($tenantId);

        return $this->success($stats);
    }

    public function completions(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'course_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $result = $this->reporting->completionReport($tenantId, $filters);

        return $this->success($result['data'], meta: [
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total' => $result['total'],
            'last_page' => (int) ceil($result['total'] / $result['per_page']),
        ]);
    }

    public function learnerProgress(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $result = $this->reporting->learnerProgressReport($tenantId, $filters);

        return $this->success($result['data'], meta: [
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total' => $result['total'],
            'last_page' => (int) ceil($result['total'] / $result['per_page']),
        ]);
    }

    public function assessments(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'quiz_id' => ['nullable', 'integer'],
            'course_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $result = $this->reporting->assessmentReport($tenantId, $filters);

        return $this->success($result['data'], meta: [
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total' => $result['total'],
            'last_page' => (int) ceil($result['total'] / $result['per_page']),
        ]);
    }

    public function questionBreakdown(int $quizId): JsonResponse
    {
        $questions = $this->reporting->questionBreakdown($quizId);

        return $this->success($questions);
    }

    public function courseDetail(int $courseId): JsonResponse
    {
        $result = $this->reporting->courseDetailReport($courseId);

        return $this->success($result);
    }
}
