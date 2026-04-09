<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
