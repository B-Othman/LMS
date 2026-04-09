<?php

namespace App\Models;

use App\Enums\LessonType;
use App\Models\LessonProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'type',
        'content_html',
        'content_json',
        'duration_minutes',
        'sort_order',
        'is_previewable',
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'content_json' => 'array',
            'is_previewable' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class)->orderBy('sort_order');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function isVideo(): bool
    {
        return $this->type === LessonType::Video;
    }

    public function isDocument(): bool
    {
        return $this->type === LessonType::Document;
    }

    public function isText(): bool
    {
        return $this->type === LessonType::Text;
    }

    public function isQuiz(): bool
    {
        return $this->type === LessonType::Quiz;
    }
}
