<?php

namespace App\Enums;

enum MediaVisibility: string
{
    case PublicAccess = 'public';
    case PrivateAccess = 'private';

    public function isPublic(): bool
    {
        return $this === self::PublicAccess;
    }

    public function isPrivate(): bool
    {
        return $this === self::PrivateAccess;
    }
}
