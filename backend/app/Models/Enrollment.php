<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Models\LessonProgress;
use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'course_id',
        'enrolled_by',
        'enrolled_at',
        'due_at',
        'completed_at',
        'status',
        'progress_percent',
        'completed_lessons_count',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => EnrollmentStatus::class,
            'progress_percent' => 'integer',
            'completed_lessons_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
