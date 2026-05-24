<?php

return [
    'default' => [
        'attempts' => (int) env('OSINT_FRONTEND_RETRY_DEFAULT_ATTEMPTS', 2),
        'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DEFAULT_BASE_DELAY_MS', 250),
        'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DEFAULT_MAX_DELAY_MS', 1500),
        'retry_on_network_error' => (bool) env('OSINT_FRONTEND_RETRY_DEFAULT_ON_NETWORK_ERROR', true),
        'retry_on_statuses' => [408, 425, 429, 500, 502, 503, 504],
    ],
    'endpoint_rules' => [
        [
            'path' => '/telegram/parser/status/',
            'methods' => ['GET'],
            'policy' => [
                'attempts' => (int) env('OSINT_FRONTEND_RETRY_PARSER_STATUS_ATTEMPTS', 4),
                'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_PARSER_STATUS_BASE_DELAY_MS', 120),
                'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_PARSER_STATUS_MAX_DELAY_MS', 1200),
            ],
        ],
        [
            'path' => '/telegram/parser/start',
            'methods' => ['POST'],
            'policy' => [
                'attempts' => 0,
                'retry_on_network_error' => false,
            ],
        ],
        [
            'path' => '/telegram/parser/stop/',
            'methods' => ['POST'],
            'policy' => [
                'attempts' => 0,
                'retry_on_network_error' => false,
            ],
        ],
        [
            'path' => '/telegram/analytics/summary',
            'methods' => ['GET'],
            'policy' => [
                'attempts' => (int) env('OSINT_FRONTEND_RETRY_TG_ANALYTICS_ATTEMPTS', 3),
                'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_TG_ANALYTICS_BASE_DELAY_MS', 180),
                'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_TG_ANALYTICS_MAX_DELAY_MS', 1200),
            ],
        ],
        [
            'path' => '/site-intel/seo-audit',
            'methods' => ['GET'],
            'policy' => [
                'attempts' => (int) env('OSINT_FRONTEND_RETRY_SEO_AUDIT_ATTEMPTS', 1),
                'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SEO_AUDIT_BASE_DELAY_MS', 300),
                'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SEO_AUDIT_MAX_DELAY_MS', 1200),
            ],
        ],
        [
            'path' => '/site-intel/analytics',
            'methods' => ['GET'],
            'policy' => [
                'attempts' => (int) env('OSINT_FRONTEND_RETRY_SITE_ANALYTICS_ATTEMPTS', 2),
                'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_ANALYTICS_BASE_DELAY_MS', 250),
                'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_ANALYTICS_MAX_DELAY_MS', 1200),
            ],
        ],
        [
            'path' => '/site-intel/site-health',
            'methods' => ['GET'],
            'policy' => [
                'attempts' => (int) env('OSINT_FRONTEND_RETRY_SITE_HEALTH_ATTEMPTS', 2),
                'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_HEALTH_BASE_DELAY_MS', 220),
                'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_HEALTH_MAX_DELAY_MS', 1000),
            ],
        ],
        [
            'path' => '/site-intel/domain-lite',
            'methods' => ['GET'],
            'policy' => [
                'attempts' => (int) env('OSINT_FRONTEND_RETRY_DOMAIN_LITE_ATTEMPTS', 2),
                'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DOMAIN_LITE_BASE_DELAY_MS', 200),
                'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DOMAIN_LITE_MAX_DELAY_MS', 1000),
            ],
        ],
    ],
];
