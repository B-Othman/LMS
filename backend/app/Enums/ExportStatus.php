<?php

namespace App\Enums;

enum ExportStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
