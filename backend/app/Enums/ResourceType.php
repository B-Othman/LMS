<?php

namespace App\Enums;

enum ResourceType: string
{
    case Primary = 'primary';
    case Supplementary = 'supplementary';
    case Download = 'download';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::Supplementary => 'Supplementary',
            self::Download => 'Download',
        };
    }
}
