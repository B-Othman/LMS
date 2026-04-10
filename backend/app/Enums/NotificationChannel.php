<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case InApp = 'in_app';
    case Both = 'both';
}
