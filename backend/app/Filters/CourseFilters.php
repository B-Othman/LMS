<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class CourseFilters
{
    /** @param array<string, mixed> $filters */
    public function __construct(
        private readonly array $filters,
    ) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);
        $this->applyStatus($query);
        $this->applyVisibility($query);
        $this->applyCategory($query);
        $this->applySorting($query);

        return $query;
    }

    private function applySearch(Builder $query): void
    {
        $search = trim((string) ($this->filters['search'] ?? ''));

        if ($search === '') {
            return;
        }

        $term = '%'.mb_strtolower($search).'%';

        $query->where(function (Builder $builder) use ($term) {
            $builder->whereRaw('LOWER(title) LIKE ?', [$term])
                ->orWhereRaw('LOWER(short_description) LIKE ?', [$term]);
        });
    }

    private function applyStatus(Builder $query): void
    {
        $status = $this->filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }
    }

    private function applyVisibility(Builder $query): void
    {
        $visibility = $this->filters['visibility'] ?? null;

        if (is_string($visibility) && $visibility !== '') {
            $query->where('visibility', $visibility);
        }
    }

    private function applyCategory(Builder $query): void
    {
        $categoryId = $this->filters['category_id'] ?? null;

        if ($categoryId !== null && $categoryId !== '') {
            $query->where('category_id', (int) $categoryId);
        }
    }

    private function applySorting(Builder $query): void
    {
        $sortBy = (string) ($this->filters['sort_by'] ?? 'created_at');
        $direction = strtolower((string) ($this->filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowed = ['title', 'status', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $direction);
    }
}
