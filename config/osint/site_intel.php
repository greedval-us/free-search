<?php

return [
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
];
