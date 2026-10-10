<?php

return [
    'timezone' => env('SITE_INTEL_REPORTS_TIMEZONE', 'Europe/Moscow'),
    'max_targets' => 3,
    'max_schedules' => 5,
    'list_page_size' => 20,
    'dispatch_batch' => 100,
    'max_attempts' => 3,
    'retry_seconds' => 300,
    'lease_seconds' => (int) env('SITE_INTEL_REPORTS_LEASE_SECONDS', 1200),
    'queue' => [
        'connection' => env('SITE_INTEL_REPORTS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database') === 'redis'
            ? 'site-intel-reports-redis' : 'site-intel-reports-database'),
        'name' => env('SITE_INTEL_REPORTS_QUEUE', 'site-intel-reports'),
        'timeout' => (int) env('SITE_INTEL_REPORTS_TIMEOUT', 900),
    ],
];
