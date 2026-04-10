<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Overview KPIs for the admin dashboard.
     *
     * @return array{
     *   total_users: int,
     *   total_courses: int,
     *   total_enrollments: int,
     *   enrollments_this_month: int,
     *   total_completions: int,
     *   completions_this_month: int,
     *   avg_completion_rate: float,
     *   total_certificates: int,
     *   completions_by_month: array<int, array{month: string, count: int}>
     * }
     */
    public function overviewStats(int $tenantId): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $totalUsers = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->count();

        $totalCourses = DB::table('courses')
            ->where('tenant_id', $tenantId)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->count();

        $totalEnrollments = DB::table('enrollments')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'completed'])
            ->count();

        $enrollmentsThisMonth = DB::table('enrollments')
            ->where('tenant_id', $tenantId)
            ->where('enrolled_at', '>=', $monthStart)
            ->count();

        $totalCompletions = DB::table('enrollments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->count();

        $completionsThisMonth = DB::table('enrollments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $monthStart)
            ->count();

        $avgCompletionRate = 0.0;
        if ($totalEnrollments > 0) {
            $avgCompletionRate = round($totalCompletions * 100.0 / $totalEnrollments, 1);
        }

        $totalCertificates = DB::table('certificates')
            ->where('tenant_id', $tenantId)
            ->whereNull('revoked_at')
            ->count();

        // Completions per month for the last 12 months
        $completionsByMonth = DB::table('enrollments')
            ->selectRaw("TO_CHAR(completed_at, 'YYYY-MM') as month, COUNT(*) as count")
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $now->copy()->subMonths(11)->startOfMonth())
            ->groupByRaw("TO_CHAR(completed_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(completed_at, 'YYYY-MM')")
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'count' => (int) $row->count])
            ->values()
            ->all();

        // Top 5 courses by enrollment
        $topCourses = DB::table('enrollments as e')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->selectRaw('c.id, c.title, COUNT(e.id) as enrollment_count')
            ->where('e.tenant_id', $tenantId)
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.title')
            ->orderByDesc('enrollment_count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'enrollment_count' => (int) $row->enrollment_count,
            ])
            ->values()
            ->all();

        // Recent enrollments (last 10)
        $recentEnrollments = DB::table('enrollments as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->selectRaw("e.id, u.first_name || ' ' || u.last_name as learner_name, c.title as course_title, e.enrolled_at")
            ->where('e.tenant_id', $tenantId)
            ->orderByDesc('e.enrolled_at')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'learner_name' => $row->learner_name,
                'course_title' => $row->course_title,
                'enrolled_at' => $row->enrolled_at,
            ])
            ->values()
            ->all();

        return [
            'total_users' => $totalUsers,
            'total_courses' => $totalCourses,
            'total_enrollments' => $totalEnrollments,
            'enrollments_this_month' => $enrollmentsThisMonth,
            'total_completions' => $totalCompletions,
            'completions_this_month' => $completionsThisMonth,
            'avg_completion_rate' => $avgCompletionRate,
            'total_certificates' => $totalCertificates,
            'completions_by_month' => $completionsByMonth,
            'top_courses' => $topCourses,
            'recent_enrollments' => $recentEnrollments,
        ];
    }

    /**
     * Course-level completion statistics.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function completionReport(int $tenantId, array $filters = []): array
    {
        $query = DB::table('enrollments as e')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->leftJoin('course_categories as cat', 'cat.id', '=', 'c.category_id')
            ->selectRaw('
                c.id,
                c.title,
                cat.name as category_name,
                COUNT(e.id) as total_enrolled,
                SUM(CASE WHEN e.status = ? THEN 1 ELSE 0 END) as completed_count,
                ROUND(
                    SUM(CASE WHEN e.status = ? THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(e.id), 0),
                    1
                ) as completion_rate,
                ROUND(
                    AVG(
                        CASE WHEN e.completed_at IS NOT NULL
                        THEN EXTRACT(EPOCH FROM (e.completed_at::timestamptz - e.enrolled_at::timestamptz)) / 86400
                        END
                    ),
                    1
                ) as avg_days_to_complete
            ', ['completed', 'completed'])
            ->where('e.tenant_id', $tenantId)
            ->where('c.status', 'published')
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.title', 'cat.name');

        if (! empty($filters['course_id'])) {
            $query->where('c.id', $filters['course_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('c.category_id', $filters['category_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('e.enrolled_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('e.enrolled_at', '<=', $filters['date_to'].' 23:59:59');
        }

        $total = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count();

        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = (int) ($filters['page'] ?? 1);

        $rows = $query
            ->orderByDesc('total_enrolled')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'category_name' => $row->category_name,
                'total_enrolled' => (int) $row->total_enrolled,
                'completed_count' => (int) $row->completed_count,
                'completion_rate' => (float) $row->completion_rate,
                'avg_days_to_complete' => $row->avg_days_to_complete !== null ? (float) $row->avg_days_to_complete : null,
            ])
            ->values()
            ->all();

        return ['data' => $rows, 'total' => $total, 'per_page' => $perPage, 'page' => $page];
    }

    /**
     * Per-learner progress summary.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function learnerProgressReport(int $tenantId, array $filters = []): array
    {
        $query = DB::table('users as u')
            ->join('enrollments as e', 'e.user_id', '=', 'u.id')
            ->leftJoin('quiz_attempts as qa', function ($join) {
                $join->on('qa.user_id', '=', 'u.id')
                    ->whereIn('qa.status', ['graded', 'needs_grading']);
            })
            ->selectRaw("
                u.id,
                u.first_name || ' ' || u.last_name as name,
                u.email,
                COUNT(DISTINCT e.id) as enrolled_count,
                SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN e.status = 'active' AND e.progress_percent > 0 THEN 1 ELSE 0 END) as in_progress_count,
                ROUND(AVG(qa.score), 1) as avg_score
            ")
            ->where('u.tenant_id', $tenantId)
            ->whereNull('u.deleted_at')
            ->groupBy('u.id', 'u.first_name', 'u.last_name', 'u.email');

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw("u.first_name || ' ' || u.last_name ILIKE ?", [$search])
                    ->orWhere('u.email', 'ILIKE', $search);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->where('e.enrolled_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('e.enrolled_at', '<=', $filters['date_to'].' 23:59:59');
        }

        $total = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count();

        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = (int) ($filters['page'] ?? 1);

        $rows = $query
            ->orderByDesc('enrolled_count')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'enrolled_count' => (int) $row->enrolled_count,
                'completed_count' => (int) $row->completed_count,
                'in_progress_count' => (int) $row->in_progress_count,
                'avg_score' => $row->avg_score !== null ? (float) $row->avg_score : null,
            ])
            ->values()
            ->all();

        return ['data' => $rows, 'total' => $total, 'per_page' => $perPage, 'page' => $page];
    }

    /**
     * Per-quiz assessment statistics.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function assessmentReport(int $tenantId, array $filters = []): array
    {
        $query = DB::table('quizzes as q')
            ->join('quiz_attempts as qa', function ($join) {
                $join->on('qa.quiz_id', '=', 'q.id')
                    ->whereIn('qa.status', ['graded', 'needs_grading']);
            })
            ->join('courses as c', 'c.id', '=', 'q.course_id')
            ->selectRaw('
                q.id,
                q.title,
                c.title as course_title,
                q.pass_score,
                COUNT(qa.id) as total_attempts,
                ROUND(AVG(qa.score), 1) as avg_score,
                ROUND(
                    SUM(CASE WHEN qa.passed = true THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(qa.id), 0),
                    1
                ) as pass_rate,
                MAX(qa.score) as highest_score,
                MIN(qa.score) as lowest_score
            ')
            ->where('q.tenant_id', $tenantId)
            ->groupBy('q.id', 'q.title', 'c.title', 'q.pass_score');

        if (! empty($filters['quiz_id'])) {
            $query->where('q.id', $filters['quiz_id']);
        }

        if (! empty($filters['course_id'])) {
            $query->where('q.course_id', $filters['course_id']);
        }

        $total = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count();

        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = (int) ($filters['page'] ?? 1);

        $rows = $query
            ->orderByDesc('total_attempts')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'course_title' => $row->course_title,
                'pass_score' => (int) $row->pass_score,
                'total_attempts' => (int) $row->total_attempts,
                'avg_score' => $row->avg_score !== null ? (float) $row->avg_score : null,
                'pass_rate' => $row->pass_rate !== null ? (float) $row->pass_rate : null,
                'highest_score' => $row->highest_score !== null ? (float) $row->highest_score : null,
                'lowest_score' => $row->lowest_score !== null ? (float) $row->lowest_score : null,
            ])
            ->values()
            ->all();

        return ['data' => $rows, 'total' => $total, 'per_page' => $perPage, 'page' => $page];
    }

    /**
     * Per-question breakdown for a specific quiz.
     *
     * @return list<array{id: int, prompt: string, total_answers: int, correct_count: int, correct_rate: float}>
     */
    public function questionBreakdown(int $quizId): array
    {
        return DB::table('quiz_questions as qq')
            ->join('quiz_answers as qans', 'qans.question_id', '=', 'qq.id')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'qans.attempt_id')
            ->whereIn('qa.status', ['graded', 'needs_grading'])
            ->selectRaw('
                qq.id,
                qq.prompt,
                qq.sort_order,
                COUNT(qans.id) as total_answers,
                SUM(CASE WHEN qans.is_correct = true THEN 1 ELSE 0 END) as correct_count,
                ROUND(
                    SUM(CASE WHEN qans.is_correct = true THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(qans.id), 0),
                    1
                ) as correct_rate
            ')
            ->where('qq.quiz_id', $quizId)
            ->groupBy('qq.id', 'qq.prompt', 'qq.sort_order')
            ->orderBy('qq.sort_order')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'prompt' => $row->prompt,
                'total_answers' => (int) $row->total_answers,
                'correct_count' => (int) $row->correct_count,
                'correct_rate' => $row->correct_rate !== null ? (float) $row->correct_rate : 0.0,
            ])
            ->values()
            ->all();
    }

    /**
     * Detailed report for a single course.
     *
     * @return array{
     *   overview: array<string, mixed>,
     *   enrollment_timeline: list<array{month: string, count: int, cumulative: int}>,
     *   lesson_completion: list<array{lesson_id: int, title: string, module_title: string, completion_rate: float, completed_count: int, total_enrolled: int}>,
     *   dropoff: list<array{lesson_id: int, title: string, reached_count: int, reach_rate: float}>
     * }
     */
    public function courseDetailReport(int $courseId): array
    {
        $overview = DB::table('enrollments as e')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->selectRaw('
                c.id,
                c.title,
                COUNT(e.id) as total_enrolled,
                SUM(CASE WHEN e.status = ? THEN 1 ELSE 0 END) as completed_count,
                ROUND(
                    SUM(CASE WHEN e.status = ? THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(e.id), 0),
                    1
                ) as completion_rate,
                ROUND(
                    AVG(
                        CASE WHEN e.completed_at IS NOT NULL
                        THEN EXTRACT(EPOCH FROM (e.completed_at::timestamptz - e.enrolled_at::timestamptz)) / 86400
                        END
                    ),
                    1
                ) as avg_days_to_complete
            ', ['completed', 'completed'])
            ->where('c.id', $courseId)
            ->groupBy('c.id', 'c.title')
            ->first();

        // Monthly enrollment timeline (cumulative)
        $timelineRaw = DB::table('enrollments')
            ->selectRaw("TO_CHAR(enrolled_at, 'YYYY-MM') as month, COUNT(*) as count")
            ->where('course_id', $courseId)
            ->groupByRaw("TO_CHAR(enrolled_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(enrolled_at, 'YYYY-MM')")
            ->get();

        $cumulative = 0;
        $enrollmentTimeline = $timelineRaw->map(function ($row) use (&$cumulative) {
            $cumulative += (int) $row->count;

            return ['month' => $row->month, 'count' => (int) $row->count, 'cumulative' => $cumulative];
        })->values()->all();

        // Lesson-level completion rates
        $totalEnrolled = $overview?->total_enrolled ?? 0;

        $lessonCompletion = DB::table('lessons as l')
            ->join('modules as m', 'm.id', '=', 'l.module_id')
            ->leftJoin('lesson_progress as lp', function ($join) use ($courseId) {
                $join->on('lp.lesson_id', '=', 'l.id')
                    ->join('enrollments as e2', function ($j) use ($courseId) {
                        $j->on('e2.id', '=', 'lp.enrollment_id')
                            ->where('e2.course_id', $courseId);
                    })
                    ->where('lp.status', 'completed');
            })
            ->selectRaw('
                l.id as lesson_id,
                l.title,
                m.title as module_title,
                m.sort_order as module_sort,
                l.sort_order as lesson_sort,
                COUNT(DISTINCT lp.id) as completed_count
            ')
            ->where('m.course_id', $courseId)
            ->groupBy('l.id', 'l.title', 'm.title', 'm.sort_order', 'l.sort_order')
            ->orderBy('m.sort_order')
            ->orderBy('l.sort_order')
            ->get()
            ->map(fn ($row) => [
                'lesson_id' => $row->lesson_id,
                'title' => $row->title,
                'module_title' => $row->module_title,
                'completed_count' => (int) $row->completed_count,
                'total_enrolled' => (int) $totalEnrolled,
                'completion_rate' => $totalEnrolled > 0
                    ? round((int) $row->completed_count * 100.0 / $totalEnrolled, 1)
                    : 0.0,
            ])
            ->values()
            ->all();

        // Drop-off: how many learners reached each lesson (started it)
        $dropoff = DB::table('lessons as l')
            ->join('modules as m', 'm.id', '=', 'l.module_id')
            ->leftJoin('lesson_progress as lp', function ($join) use ($courseId) {
                $join->on('lp.lesson_id', '=', 'l.id')
                    ->join('enrollments as e2', function ($j) use ($courseId) {
                        $j->on('e2.id', '=', 'lp.enrollment_id')
                            ->where('e2.course_id', $courseId);
                    })
                    ->whereIn('lp.status', ['in_progress', 'completed']);
            })
            ->selectRaw('
                l.id as lesson_id,
                l.title,
                m.sort_order as module_sort,
                l.sort_order as lesson_sort,
                COUNT(DISTINCT lp.id) as reached_count
            ')
            ->where('m.course_id', $courseId)
            ->groupBy('l.id', 'l.title', 'm.sort_order', 'l.sort_order')
            ->orderBy('m.sort_order')
            ->orderBy('l.sort_order')
            ->get()
            ->map(fn ($row) => [
                'lesson_id' => $row->lesson_id,
                'title' => $row->title,
                'reached_count' => (int) $row->reached_count,
                'reach_rate' => $totalEnrolled > 0
                    ? round((int) $row->reached_count * 100.0 / $totalEnrolled, 1)
                    : 0.0,
            ])
            ->values()
            ->all();

        return [
            'overview' => $overview ? [
                'id' => $overview->id,
                'title' => $overview->title,
                'total_enrolled' => (int) $overview->total_enrolled,
                'completed_count' => (int) $overview->completed_count,
                'completion_rate' => (float) $overview->completion_rate,
                'avg_days_to_complete' => $overview->avg_days_to_complete !== null
                    ? (float) $overview->avg_days_to_complete
                    : null,
            ] : null,
            'enrollment_timeline' => $enrollmentTimeline,
            'lesson_completion' => $lessonCompletion,
            'dropoff' => $dropoff,
        ];
    }
}
