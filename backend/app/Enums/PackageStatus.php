<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Uploaded = 'uploaded';
    case Validating = 'validating';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Published = 'published';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Uploaded',
            self::Validating => 'Validating',
            self::Valid => 'Valid',
            self::Invalid => 'Invalid',
            self::Published => 'Published',
            self::Failed => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Invalid, self::Published, self::Failed], true);
    }
}
