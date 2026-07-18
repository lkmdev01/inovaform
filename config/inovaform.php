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
    'publication' => [
        'custom_domain_diagnostics_enabled' => (bool) env('INOVAFORM_CUSTOM_DOMAIN_DIAGNOSTICS_ENABLED', env('APP_ENV') !== 'testing'),
        'custom_domain_target_host' => env('INOVAFORM_CUSTOM_DOMAIN_TARGET_HOST') ?: parse_url((string) env('APP_URL', ''), PHP_URL_HOST),
        'custom_domain_target_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('INOVAFORM_CUSTOM_DOMAIN_TARGET_IPS', ''))))),
        'custom_domain_diagnostics_cache_minutes' => (int) env('INOVAFORM_CUSTOM_DOMAIN_DIAGNOSTICS_CACHE_MINUTES', 5),
        'custom_domain_tls_timeout_seconds' => (int) env('INOVAFORM_CUSTOM_DOMAIN_TLS_TIMEOUT_SECONDS', 3),
    ],
];
