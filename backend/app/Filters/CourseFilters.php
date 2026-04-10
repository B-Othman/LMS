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
        $this->applyTags($query);
        $this->applySorting($query);

        return $query;
    }

    private function applySearch(Builder $query): void
    {
        $search = trim((string) ($this->filters['search'] ?? ''));

        if ($search === '') {
            return;
        }

        $query->whereRaw(
            "to_tsvector('english', coalesce(title,'') || ' ' || coalesce(description,'') || ' ' || coalesce(short_description,'')) @@ plainto_tsquery('english', ?)",
            [$search],
        );
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

    private function applyTags(Builder $query): void
    {
        $tagIds = $this->filters['tag_ids'] ?? null;

        if (is_array($tagIds) && count($tagIds) > 0) {
            $query->whereHas('tags', fn (Builder $q) => $q->whereIn('course_tags.id', $tagIds));
        }
    }

    private function applySorting(Builder $query): void
    {
        $search = trim((string) ($this->filters['search'] ?? ''));

        // When searching, sort by relevance first
        if ($search !== '') {
            $query->orderByRaw(
                "ts_rank(to_tsvector('english', coalesce(title,'') || ' ' || coalesce(description,'') || ' ' || coalesce(short_description,'')), plainto_tsquery('english', ?)) DESC",
                [$search],
            );

            return;
        }

        $sortBy = (string) ($this->filters['sort_by'] ?? 'created_at');
        $direction = strtolower((string) ($this->filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowed = ['title', 'status', 'created_at', 'updated_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $direction);
    }
}
