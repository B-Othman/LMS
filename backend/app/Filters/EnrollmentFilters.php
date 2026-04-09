<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class EnrollmentFilters
{
    /** @param array<string, mixed> $filters */
    public function __construct(
        private readonly array $filters,
    ) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);
        $this->applyStatus($query);
        $this->applyCourse($query);
        $this->applyUser($query);
        $this->applySorting($query);

        return $query;
    }

    private function applySearch(Builder $query): void
    {
        $search = trim((string) ($this->filters['search'] ?? ''));

        if ($search === '') {
            return;
        }

        $terms = array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($search)) ?: [],
            fn ($value) => $value !== ''
        ));

        $query->whereHas('user', function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%'.$term.'%';

                $builder->where(function (Builder $nested) use ($like) {
                    $nested->whereRaw('LOWER(first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });
            }
        });
    }

    private function applyStatus(Builder $query): void
    {
        $status = $this->filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }
    }

    private function applyCourse(Builder $query): void
    {
        $courseId = $this->filters['course_id'] ?? null;

        if ($courseId !== null && $courseId !== '') {
            $query->where('course_id', (int) $courseId);
        }
    }

    private function applyUser(Builder $query): void
    {
        $userId = $this->filters['user_id'] ?? null;

        if ($userId !== null && $userId !== '') {
            $query->where('user_id', (int) $userId);
        }
    }

    private function applySorting(Builder $query): void
    {
        $sortBy = (string) ($this->filters['sort_by'] ?? 'enrolled_at');
        $direction = strtolower((string) ($this->filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowed = ['enrolled_at', 'due_at', 'status', 'created_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'enrolled_at';
        }

        $query->orderBy($sortBy, $direction)->orderBy('id', $direction);
    }
}
