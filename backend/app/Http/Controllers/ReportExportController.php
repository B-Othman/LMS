<?php

namespace App\Http\Controllers;

use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Enums\ReportType;
use App\Http\Resources\ReportExportResource;
use App\Jobs\ExportReportJob;
use App\Models\ReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportExportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $exports = ReportExport::where('tenant_id', $user->tenant_id)
            ->where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return $this->success(ReportExportResource::collection($exports)->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'report_type' => ['required', Rule::enum(ReportType::class)],
            'format' => ['required', Rule::enum(ExportFormat::class)],
            'filters' => ['nullable', 'array'],
            'filters.course_id' => ['nullable', 'integer'],
            'filters.category_id' => ['nullable', 'integer'],
            'filters.quiz_id' => ['nullable', 'integer'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date'],
            'filters.search' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $export = ReportExport::create([
            'tenant_id' => $user->tenant_id,
            'requested_by' => $user->id,
            'report_type' => $data['report_type'],
            'format' => $data['format'],
            'filters' => $data['filters'] ?? [],
            'status' => ExportStatus::Processing->value,
        ]);

        ExportReportJob::dispatch($export->id);

        return $this->success(new ReportExportResource($export), 'Export started.', 202);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $export = ReportExport::where('tenant_id', $request->user()->tenant_id)
            ->where('requested_by', $request->user()->id)
            ->findOrFail($id);

        return $this->success(new ReportExportResource($export));
    }
}
