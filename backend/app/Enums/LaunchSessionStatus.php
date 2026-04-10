<?php

namespace App\Enums;

enum LaunchSessionStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case Abandoned = 'abandoned';
}
