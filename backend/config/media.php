<?php

return [
    'disk' => env('MEDIA_DISK', 's3'),

    'signed_url_expiry_minutes' => (int) env('MEDIA_SIGNED_URL_EXPIRY_MINUTES', 15),

    'image_thumbnail_width' => (int) env('MEDIA_IMAGE_THUMBNAIL_WIDTH', 360),

    'image_thumbnail_quality' => (int) env('MEDIA_IMAGE_THUMBNAIL_QUALITY', 82),

    'allowed_mime_types' => [
        'video/mp4' => 500 * 1024 * 1024,
        'video/webm' => 500 * 1024 * 1024,
        'application/pdf' => 50 * 1024 * 1024,
        'image/jpeg' => 10 * 1024 * 1024,
        'image/png' => 10 * 1024 * 1024,
        'image/webp' => 10 * 1024 * 1024,
    ],
];
