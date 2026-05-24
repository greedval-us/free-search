<?php

return [
    'http' => [
        'user_agent' => env('OSINT_FIO_HTTP_USER_AGENT', 'FreeSearch-FIO/1.0'),
        'timeout_seconds' => (int) env('OSINT_FIO_HTTP_TIMEOUT', 12),
        'retry_attempts' => (int) env('OSINT_FIO_HTTP_RETRY_ATTEMPTS', 1),
        'retry_sleep_milliseconds' => (int) env('OSINT_FIO_HTTP_RETRY_SLEEP', 250),
    ],
];
