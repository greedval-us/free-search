<?php

$moonShinePrefix = trim((string) env('MOONSHINE_ROUTE_PREFIX', 'admin'), '/');

return [
    'rate_limits' => [
        'registration' => [
            'per_minute' => max(1, (int) env('SECURITY_REGISTRATION_PER_MINUTE', 5)),
            'per_hour' => max(1, (int) env('SECURITY_REGISTRATION_PER_HOUR', 20)),
        ],
        'password_reset' => [
            'per_minute' => max(1, (int) env('SECURITY_PASSWORD_RESET_PER_MINUTE', 5)),
            'per_hour' => max(1, (int) env('SECURITY_PASSWORD_RESET_PER_HOUR', 20)),
            'per_email_per_minute' => max(1, (int) env('SECURITY_PASSWORD_RESET_PER_EMAIL_PER_MINUTE', 3)),
        ],
        'resend_webhook' => [
            'per_minute' => max(1, (int) env('SECURITY_RESEND_WEBHOOK_PER_MINUTE', 60)),
        ],
    ],
    'headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', env('APP_ENV') === 'production'),
        'values' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), geolocation=(), microphone=(), payment=(), usb=()',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ],
    ],
    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
        'value' => 'max-age=31536000; includeSubDomains',
    ],
    'content_security_policy' => [
        'enabled' => (bool) env('SECURITY_CSP_ENABLED', true),
        'excluded_paths' => array_values(array_filter(array_map(
            static fn (string $path): string => trim($path),
            explode(',', (string) env(
                'SECURITY_CSP_EXCLUDED_PATHS',
                "{$moonShinePrefix},{$moonShinePrefix}/*"
            ))
        ))),
        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'connect-src' => ["'self'"],
            'font-src' => ["'self'", 'data:', 'https://fonts.bunny.net'],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'img-src' => ["'self'", 'blob:', 'data:', 'https:'],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'object-src' => ["'none'"],
            'script-src' => ["'self'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'],
            'upgrade-insecure-requests' => [],
        ],
    ],
];
