<?php

return [
    'timezone' => env('TELEGRAM_ANALYTICS_REPORTS_TIMEZONE', 'Europe/Moscow'),
    'max_groups' => 3,
    'max_schedules' => 5,
    'list_page_size' => 20,
    'dispatch_batch' => 100,
    'catchup_per_schedule' => 10,
    'max_attempts' => 3,
    'retry_seconds' => 300,
    'lease_seconds' => 300,
    'queue' => [
        'connection' => env('TELEGRAM_ANALYTICS_REPORTS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('TELEGRAM_ANALYTICS_REPORTS_QUEUE', 'telegram-analytics-reports'),
        'timeout' => 120,
    ],
];
