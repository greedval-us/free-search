<?php

return [
    'reports' => [
        'generated_at_format' => 'd.m.Y H:i',
        'filename_timestamp_format' => 'Ymd-His',
        'download_content_type' => 'text/html; charset=UTF-8',
    ],

    'fio' => [
        'http' => [
            'user_agent' => env('OSINT_FIO_HTTP_USER_AGENT', 'FreeSearch-FIO/1.0'),
            'timeout_seconds' => (int) env('OSINT_FIO_HTTP_TIMEOUT', 12),
            'retry_attempts' => (int) env('OSINT_FIO_HTTP_RETRY_ATTEMPTS', 1),
            'retry_sleep_milliseconds' => (int) env('OSINT_FIO_HTTP_RETRY_SLEEP', 250),
        ],
    ],

    'site_intel' => [
        'http' => [
            'user_agent' => env('OSINT_SITE_HEALTH_HTTP_USER_AGENT', 'FreeSearch-SiteHealth/1.0'),
            'accept' => env('OSINT_SITE_HEALTH_HTTP_ACCEPT', '*/*'),
            'timeout_seconds' => (int) env('OSINT_SITE_HEALTH_HTTP_TIMEOUT', 10),
            'max_redirects' => (int) env('OSINT_SITE_HEALTH_HTTP_MAX_REDIRECTS', 5),
            'verify_ssl' => (bool) env('OSINT_SITE_HEALTH_HTTP_VERIFY_SSL', false),
        ],
        'whois' => [
            'iana_server' => env('OSINT_SITE_INTEL_WHOIS_IANA_SERVER', 'whois.iana.org'),
            'connect_timeout_seconds' => (int) env('OSINT_SITE_INTEL_WHOIS_CONNECT_TIMEOUT', 8),
            'read_timeout_seconds' => (int) env('OSINT_SITE_INTEL_WHOIS_READ_TIMEOUT', 8),
            'read_chunk_size' => (int) env('OSINT_SITE_INTEL_WHOIS_READ_CHUNK_SIZE', 2048),
            'max_response_bytes' => (int) env('OSINT_SITE_INTEL_WHOIS_MAX_RESPONSE_BYTES', 120000),
        ],
    ],

    'username' => [
        'http' => [
            'connect_timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_CONNECT_TIMEOUT', 6),
            'timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_TIMEOUT', 8),
            'max_redirects' => (int) env('OSINT_USERNAME_HTTP_MAX_REDIRECTS', 5),
            'user_agent' => env('OSINT_USERNAME_HTTP_USER_AGENT', 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)'),
            'accept' => env('OSINT_USERNAME_HTTP_ACCEPT', 'text/html,application/xhtml+xml'),
        ],
    ],

    'telegram' => [
        'analytics' => [
            'period_min_days' => (int) env('OSINT_TELEGRAM_ANALYTICS_PERIOD_MIN_DAYS', 1),
            'period_max_days' => (int) env('OSINT_TELEGRAM_ANALYTICS_PERIOD_MAX_DAYS', 7),
            'custom_range_max_days' => (int) env('OSINT_TELEGRAM_ANALYTICS_CUSTOM_RANGE_MAX_DAYS', 7),
        ],
        'search' => [
            'messages_limit_default' => (int) env('OSINT_TELEGRAM_MESSAGES_LIMIT_DEFAULT', 20),
            'messages_limit_max' => (int) env('OSINT_TELEGRAM_MESSAGES_LIMIT_MAX', 100),
            'comments_limit_default' => (int) env('OSINT_TELEGRAM_COMMENTS_LIMIT_DEFAULT', 20),
            'comments_limit_max' => (int) env('OSINT_TELEGRAM_COMMENTS_LIMIT_MAX', 50),
        ],
        'parser' => [
            'custom_range_max_days' => (int) env('OSINT_TELEGRAM_PARSER_CUSTOM_RANGE_MAX_DAYS', 31),
        ],
    ],
];
