<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserFilters
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters,
    ) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);
        $this->applyStatus($query);
        $this->applyRole($query);
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
            $builder->whereRaw('LOWER(first_name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$term]);
        });
    }

    private function applyStatus(Builder $query): void
    {
        $status = $this->filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }
    }

    private function applyRole(Builder $query): void
    {
        $role = $this->filters['role'] ?? null;

        if (is_string($role) && $role !== '') {
            $query->whereHas('roles', fn (Builder $builder) => $builder->where('slug', $role));
        }
    }

    private function applySorting(Builder $query): void
    {
        $sortBy = (string) ($this->filters['sort_by'] ?? 'created_at');
        $direction = strtolower((string) ($this->filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'name') {
            $query->orderBy('first_name', $direction)
                ->orderBy('last_name', $direction);

            return;
        }

        $allowed = ['email', 'status', 'last_login_at', 'created_at'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $direction);
    }
}
