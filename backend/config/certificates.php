<?php

return [
    'disk' => env('CERTIFICATES_DISK', env('MEDIA_DISK', 's3')),

    'background_disk' => env('CERTIFICATE_TEMPLATE_BACKGROUND_DISK', env('CERTIFICATES_DISK', env('MEDIA_DISK', 's3'))),

    'signed_url_expiry_minutes' => (int) env('CERTIFICATES_SIGNED_URL_EXPIRY_MINUTES', 15),

    'background_url_expiry_minutes' => (int) env('CERTIFICATE_TEMPLATE_BACKGROUND_URL_EXPIRY_MINUTES', 30),

    'preview' => [
        'learner_name' => env('CERTIFICATE_PREVIEW_LEARNER_NAME', 'Avery Carter'),
        'course_title' => env('CERTIFICATE_PREVIEW_COURSE_TITLE', 'Applied Security Foundations'),
        'completion_date' => env('CERTIFICATE_PREVIEW_COMPLETION_DATE', 'April 9, 2026'),
        'certificate_id' => env('CERTIFICATE_PREVIEW_CERTIFICATE_ID', 'PREVIEW-2026-001'),
        'verification_code' => env('CERTIFICATE_PREVIEW_VERIFICATION_CODE', 'SCY-PREV-26A1'),
    ],
];
