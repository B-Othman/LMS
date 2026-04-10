<?php

namespace App\Jobs;

use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Enums\ReportType;
use App\Models\ReportExport;
use App\Services\AuditService;
use App\Services\ReportingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly int $exportId,
    ) {}

    public function handle(ReportingService $reporting, AuditService $audit): void
    {
        $export = ReportExport::find($this->exportId);

        if (! $export) {
            return;
        }

        $audit->log(
            'export.requested',
            $export,
            $export->requested_by,
            $export->tenant_id,
            "Report export requested: {$export->report_type->label()} ({$export->format->value})",
        );

        $filters = $export->filters ?? [];
        $tenantId = $export->tenant_id;

        [$columns, $rows, $title] = match ($export->report_type) {
            ReportType::Completions => $this->completionsData($reporting, $tenantId, $filters),
            ReportType::LearnerProgress => $this->learnerProgressData($reporting, $tenantId, $filters),
            ReportType::Assessments => $this->assessmentsData($reporting, $tenantId, $filters),
            ReportType::CourseDetail => $this->courseDetailData($reporting, $filters),
        };

        $content = match ($export->format) {
            ExportFormat::Csv => $this->buildCsv($columns, $rows),
            ExportFormat::Pdf => $this->buildPdf($title, $export->report_type->value, $export->id, $columns, $rows),
        };

        $ext = $export->format->value;
        $path = sprintf(
            'tenants/%d/exports/%s/%d.%s',
            $tenantId,
            now()->format('Y'),
            $export->id,
            $ext,
        );

        Storage::disk((string) config('filesystems.default', 's3'))
            ->put($path, $content, ['visibility' => 'private']);

        $export->update([
            'file_path' => $path,
            'status' => ExportStatus::Ready->value,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function failed(Throwable $e): void
    {
        ReportExport::where('id', $this->exportId)->update([
            'status' => ExportStatus::Failed->value,
            'error_message' => $e->getMessage(),
        ]);
    }

    /**
     * @return array{list<string>, list<array<string, mixed>>, string}
     */
    private function completionsData(ReportingService $reporting, int $tenantId, array $filters): array
    {
        $result = $reporting->completionReport($tenantId, array_merge($filters, ['per_page' => 10000]));

        $columns = ['Course', 'Category', 'Total Enrolled', 'Completed', 'Completion Rate (%)', 'Avg Days to Complete'];

        $rows = array_map(fn ($row) => [
            'title' => $row['title'],
            'category_name' => $row['category_name'] ?? '—',
            'total_enrolled' => $row['total_enrolled'],
            'completed_count' => $row['completed_count'],
            'completion_rate' => $row['completion_rate'],
            'avg_days_to_complete' => $row['avg_days_to_complete'] ?? '—',
        ], $result['data']);

        return [$columns, $rows, 'Course Completions Report'];
    }

    /**
     * @return array{list<string>, list<array<string, mixed>>, string}
     */
    private function learnerProgressData(ReportingService $reporting, int $tenantId, array $filters): array
    {
        $result = $reporting->learnerProgressReport($tenantId, array_merge($filters, ['per_page' => 10000]));

        $columns = ['Name', 'Email', 'Enrolled', 'Completed', 'In Progress', 'Avg Score (%)'];

        $rows = array_map(fn ($row) => [
            'name' => $row['name'],
            'email' => $row['email'],
            'enrolled_count' => $row['enrolled_count'],
            'completed_count' => $row['completed_count'],
            'in_progress_count' => $row['in_progress_count'],
            'avg_score' => $row['avg_score'] ?? '—',
        ], $result['data']);

        return [$columns, $rows, 'Learner Progress Report'];
    }

    /**
     * @return array{list<string>, list<array<string, mixed>>, string}
     */
    private function assessmentsData(ReportingService $reporting, int $tenantId, array $filters): array
    {
        $result = $reporting->assessmentReport($tenantId, array_merge($filters, ['per_page' => 10000]));

        $columns = ['Quiz', 'Course', 'Pass Score (%)', 'Total Attempts', 'Avg Score (%)', 'Pass Rate (%)', 'Highest', 'Lowest'];

        $rows = array_map(fn ($row) => [
            'title' => $row['title'],
            'course_title' => $row['course_title'],
            'pass_score' => $row['pass_score'],
            'total_attempts' => $row['total_attempts'],
            'avg_score' => $row['avg_score'] ?? '—',
            'pass_rate' => $row['pass_rate'] ?? '—',
            'highest_score' => $row['highest_score'] ?? '—',
            'lowest_score' => $row['lowest_score'] ?? '—',
        ], $result['data']);

        return [$columns, $rows, 'Assessment Analytics Report'];
    }

    /**
     * @return array{list<string>, list<array<string, mixed>>, string}
     */
    private function courseDetailData(ReportingService $reporting, array $filters): array
    {
        $courseId = (int) ($filters['course_id'] ?? 0);
        $result = $reporting->courseDetailReport($courseId);

        $columns = ['Lesson', 'Module', 'Completed Count', 'Total Enrolled', 'Completion Rate (%)'];

        $rows = array_map(fn ($row) => [
            'title' => $row['title'],
            'module_title' => $row['module_title'],
            'completed_count' => $row['completed_count'],
            'total_enrolled' => $row['total_enrolled'],
            'completion_rate' => $row['completion_rate'],
        ], $result['lesson_completion']);

        $title = 'Course Detail Report';
        if ($result['overview']) {
            $title = 'Course Detail: '.$result['overview']['title'];
        }

        return [$columns, $rows, $title];
    }

    /**
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private function buildCsv(array $columns, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Could not open temp stream for CSV generation.');
        }

        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    /**
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private function buildPdf(string $title, string $reportType, int $exportId, array $columns, array $rows): string
    {
        $pdf = Pdf::loadView('reports.export', compact('title', 'reportType', 'exportId', 'columns', 'rows'))
            ->setPaper('a4', 'landscape');

        return $pdf->output();
    }
}
