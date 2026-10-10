<?php

return [
    'timezone' => env('YOUTUBE_ANALYTICS_REPORTS_TIMEZONE', 'Europe/Moscow'),
    'max_channels' => 3,
    'max_schedules' => 5,
    'list_page_size' => 20,
    'fetch_max_pages' => (int) env('YOUTUBE_ANALYTICS_REPORTS_FETCH_MAX_PAGES', 100),
    'dispatch_batch' => 100,
    'catchup_per_schedule' => 10,
    'max_attempts' => 3,
    'retry_seconds' => 300,
    'lease_seconds' => 300,
    'queue' => [
        'connection' => env('YOUTUBE_ANALYTICS_REPORTS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('YOUTUBE_ANALYTICS_REPORTS_QUEUE', 'youtube-analytics-reports'),
        'timeout' => 120,
    ],
];
