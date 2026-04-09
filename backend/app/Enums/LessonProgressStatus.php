<?php

namespace App\Enums;

enum LessonProgressStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }

    public function progressPercentage(): int
    {
        return match ($this) {
            self::Completed => 100,
            default => 0,
        };
    }
}
