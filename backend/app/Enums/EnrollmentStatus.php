<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Dropped = 'dropped';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Dropped => 'Dropped',
            self::Expired => 'Expired',
        };
    }

    public function progressPercentage(): int
    {
        return $this === self::Completed ? 100 : 0;
    }

    public function canBeDropped(): bool
    {
        return $this !== self::Completed && $this !== self::Dropped;
    }
}
