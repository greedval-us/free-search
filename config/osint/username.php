<?php

return [
    'http' => [
        'connect_timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_CONNECT_TIMEOUT', 6),
        'timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_TIMEOUT', 8),
        'max_redirects' => (int) env('OSINT_USERNAME_HTTP_MAX_REDIRECTS', 5),
        'user_agent' => env('OSINT_USERNAME_HTTP_USER_AGENT', 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)'),
        'accept' => env('OSINT_USERNAME_HTTP_ACCEPT', 'text/html,application/xhtml+xml'),
    ],
];
