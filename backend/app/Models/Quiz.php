<?php

namespace App\Models;

use App\Enums\QuizStatus;
use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'tenant_id',
        'title',
        'description',
        'pass_score',
        'time_limit_minutes',
        'attempts_allowed',
        'shuffle_questions',
        'show_results_to_learner',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pass_score' => 'integer',
            'time_limit_minutes' => 'integer',
            'attempts_allowed' => 'integer',
            'shuffle_questions' => 'boolean',
            'show_results_to_learner' => 'boolean',
            'status' => QuizStatus::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class)->orderByDesc('started_at');
    }

    public function isPublished(): bool
    {
        return $this->status === QuizStatus::Published;
    }
}
