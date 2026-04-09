<?php

namespace App\Enums;

enum CertificateTemplateLayout: string
{
    case Landscape = 'landscape';
    case Portrait = 'portrait';

    public function label(): string
    {
        return match ($this) {
            self::Landscape => 'Landscape',
            self::Portrait => 'Portrait',
        };
    }

    public function paperOrientation(): string
    {
        return $this->value;
    }
}
