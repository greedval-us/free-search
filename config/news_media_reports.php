<?php

return [
    'timezone' => env('NEWS_MEDIA_REPORTS_TIMEZONE', 'Europe/Moscow'),
    'max_queries' => 3,
    'max_schedules' => 5,
    'list_page_size' => 20,
    'dispatch_batch' => 100,
    'max_attempts' => 3,
    'retry_seconds' => 300,
    'lease_seconds' => (int) env('NEWS_MEDIA_REPORTS_LEASE_SECONDS', 300),
    'queue' => [
        'connection' => env('NEWS_MEDIA_REPORTS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database') === 'redis'
            ? 'news-media-reports-redis' : 'news-media-reports-database'),
        'name' => env('NEWS_MEDIA_REPORTS_QUEUE', 'news-media-reports'),
        'timeout' => (int) env('NEWS_MEDIA_REPORTS_TIMEOUT', 120),
    ],
];
