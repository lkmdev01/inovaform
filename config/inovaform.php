<?php

return [
    'media' => [
        'disk' => env('INOVAFORM_MEDIA_DISK', env('INOVAFORM_IMAGE_DISK', 'public')),
        'cleanup_retention_days' => (int) env('INOVAFORM_MEDIA_CLEANUP_RETENTION_DAYS', 14),
        'orphan_alert_threshold' => (int) env('INOVAFORM_MEDIA_ORPHAN_ALERT_THRESHOLD', 25),
    ],
    'observability' => [
        'critical_event_sample_limit' => (int) env('INOVAFORM_CRITICAL_EVENT_SAMPLE_LIMIT', 50),
    ],
];
