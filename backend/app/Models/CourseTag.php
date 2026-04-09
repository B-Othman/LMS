<?php

namespace App\Models;

use App\Models\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseTag extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'course_tags';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_tag_pivot', 'tag_id', 'course_id');
    }
}
