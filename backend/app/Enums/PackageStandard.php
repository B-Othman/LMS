<?php

namespace App\Enums;

enum PackageStandard: string
{
    case Scorm12 = 'scorm_12';
    case Scorm2004 = 'scorm_2004';
    case Xapi = 'xapi';
    case Native = 'native';

    public function label(): string
    {
        return match ($this) {
            self::Scorm12 => 'SCORM 1.2',
            self::Scorm2004 => 'SCORM 2004',
            self::Xapi => 'xAPI',
            self::Native => 'Native',
        };
    }

    public function isSupported(): bool
    {
        return $this === self::Scorm12;
    }
}
