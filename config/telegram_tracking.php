<?php

return [
    'limits' => ['free' => 1, 'plus' => 3, 'pro' => 5],
    'max_sources' => 3,
    'max_open_tasks' => 5,
    'keyword_min_length' => 3,
    'keyword_max_length' => 120,
    'duration_months' => 1,
    'renewal_window_days' => 7,
    'retention_days' => 7,
    'interval_hours' => (int) env('TELEGRAM_TRACKING_INTERVAL_HOURS', 6),
    'max_interval_hours' => 24,
    'sources_per_interval' => 30,
    'page_size' => 100,
    'list_page_size' => 20,
    'message_page_size' => 30,
    'dispatch_batch' => 100,
    'request_gap_seconds' => 3,
    'retry_seconds' => 300,
    'max_failure_exponent' => 10,
    'lease_seconds' => 300,
    'validation_cache_seconds' => 300,
    'queue' => [
        'connection' => env('TELEGRAM_TRACKING_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('TELEGRAM_TRACKING_QUEUE', 'telegram-tracking'),
        'timeout' => 60,
    ],
];
