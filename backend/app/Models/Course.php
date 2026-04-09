<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\CourseVisibility;
use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'short_description',
        'thumbnail_path',
        'status',
        'visibility',
        'category_id',
        'certificate_template_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'visibility' => CourseVisibility::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CourseTag::class, 'course_tag_pivot', 'course_id', 'tag_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isDraft(): bool
    {
        return $this->status === CourseStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    public function isArchived(): bool
    {
        return $this->status === CourseStatus::Archived;
    }
}
