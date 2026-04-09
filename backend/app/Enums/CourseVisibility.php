<?php

namespace App\Enums;

enum CourseVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Restricted = 'restricted';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private',
            self::Restricted => 'Restricted',
        };
    }
}
