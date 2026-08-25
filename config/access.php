<?php

$baseLimit = static fn (string $plan, string $capability, int $default): int => max(
    0,
    (int) env(sprintf('ACCESS_%s_%s_DAILY_LIMIT', strtoupper($plan), strtoupper(str_replace('-', '_', $capability))), $default)
);

$resourceLimit = static fn (string $plan, string $resource, int $default): int => max(
    0,
    (int) env(sprintf('ACCESS_%s_%s_DAILY_LIMIT', strtoupper($plan), strtoupper(str_replace(['-', '.'], '_', $resource))), $default)
);

$planLimits = [
    'free' => [
        'analytics' => $baseLimit('free', 'analytics', 1),
        'parser' => $baseLimit('free', 'parser', 1),
        'seo-audit' => $baseLimit('free', 'seo-audit', 1),
    ],
    'plus' => [
        'analytics' => $baseLimit('plus', 'analytics', 10),
        'parser' => $baseLimit('plus', 'parser', 5),
        'seo-audit' => $baseLimit('plus', 'seo-audit', 10),
    ],
    'pro' => [
        'analytics' => $baseLimit('pro', 'analytics', 100),
        'parser' => $baseLimit('pro', 'parser', 50),
        'seo-audit' => $baseLimit('pro', 'seo-audit', 100),
    ],
];

$resourceQuotas = static function (string $plan) use ($planLimits, $resourceLimit): array {
    $limits = $planLimits[$plan];

    return [
        'analytics' => $limits['analytics'],
        'parser' => $limits['parser'],
        'bluesky.analytics' => $resourceLimit($plan, 'bluesky.analytics', $limits['analytics']),
        'bluesky.parser' => $resourceLimit($plan, 'bluesky.parser', $limits['parser']),
        'mastodon.analytics' => $resourceLimit($plan, 'mastodon.analytics', $limits['analytics']),
        'mastodon.parser' => $resourceLimit($plan, 'mastodon.parser', $limits['parser']),
        'site-intel.analytics' => $resourceLimit($plan, 'site-intel.analytics', $limits['analytics']),
        'site-intel.seo-audit' => $resourceLimit($plan, 'site-intel.seo-audit', $limits['seo-audit']),
        'telegram.analytics' => $resourceLimit($plan, 'telegram.analytics', $limits['analytics']),
        'telegram.parser' => $resourceLimit($plan, 'telegram.parser', $limits['parser']),
        'youtube.analytics' => $resourceLimit($plan, 'youtube.analytics', $limits['analytics']),
        'youtube.parser' => $resourceLimit($plan, 'youtube.parser', $limits['parser']),
    ];
};

return [
    'checkout_enabled' => (bool) env('BILLING_CHECKOUT_ENABLED', false),

    'plans' => [
        'free' => $resourceQuotas('free'),
        'plus' => $resourceQuotas('plus'),
        'pro' => $resourceQuotas('pro'),
    ],

    'resources' => [
        'bluesky.analytics' => [
            'module' => 'bluesky',
            'capability' => 'analytics',
            'quota_key' => 'bluesky.analytics',
        ],
        'bluesky.parser' => [
            'module' => 'bluesky',
            'capability' => 'parser',
            'quota_key' => 'bluesky.parser',
        ],
        'mastodon.analytics' => [
            'module' => 'mastodon',
            'capability' => 'analytics',
            'quota_key' => 'mastodon.analytics',
        ],
        'mastodon.parser' => [
            'module' => 'mastodon',
            'capability' => 'parser',
            'quota_key' => 'mastodon.parser',
        ],
        'site-intel.analytics' => [
            'module' => 'site-intel',
            'capability' => 'analytics',
            'quota_key' => 'site-intel.analytics',
        ],
        'site-intel.seo-audit' => [
            'module' => 'site-intel',
            'capability' => 'seo-audit',
            'quota_key' => 'site-intel.seo-audit',
        ],
        'telegram.analytics' => [
            'module' => 'telegram',
            'capability' => 'analytics',
            'quota_key' => 'telegram.analytics',
        ],
        'telegram.parser' => [
            'module' => 'telegram',
            'capability' => 'parser',
            'quota_key' => 'telegram.parser',
        ],
        'youtube.analytics' => [
            'module' => 'youtube',
            'capability' => 'analytics',
            'quota_key' => 'youtube.analytics',
        ],
        'youtube.parser' => [
            'module' => 'youtube',
            'capability' => 'parser',
            'quota_key' => 'youtube.parser',
        ],
    ],

    'bypass_account_types' => [
        'admin',
        'moderator',
    ],

    'page_resources' => [
        'bluesky' => [
            'tabs' => [
                'analytics' => 'bluesky.analytics',
                'parser' => 'bluesky.parser',
            ],
        ],
        'mastodon' => [
            'tabs' => [
                'analytics' => 'mastodon.analytics',
                'parser' => 'mastodon.parser',
            ],
        ],
        'site-intel' => [
            'tabs' => [
                'analytics' => 'site-intel.analytics',
                'seoAudit' => 'site-intel.seo-audit',
            ],
        ],
        'telegram' => [
            'tabs' => [
                'analytics' => 'telegram.analytics',
                'parser' => 'telegram.parser',
            ],
        ],
        'youtube' => [
            'tabs' => [
                'analytics' => 'youtube.analytics',
                'parser' => 'youtube.parser',
            ],
        ],
    ],

    'non_counting_query_values' => [
        'snapshotRole' => [
            'previous',
        ],
    ],

    'protected_routes' => [
        'bluesky.analytics.summary' => ['resource' => 'bluesky.analytics', 'counts' => true],
        'bluesky.analytics.report' => ['resource' => 'bluesky.analytics', 'counts' => false],
        'bluesky.parser.start' => ['resource' => 'bluesky.parser', 'counts' => true],
        'bluesky.parser.status' => ['resource' => 'bluesky.parser', 'counts' => false],
        'bluesky.parser.stop' => ['resource' => 'bluesky.parser', 'counts' => false],
        'bluesky.parser.download-excel' => ['resource' => 'bluesky.parser', 'counts' => false],
        'bluesky.parser.download-json' => ['resource' => 'bluesky.parser', 'counts' => false],
        'mastodon.analytics.summary' => ['resource' => 'mastodon.analytics', 'counts' => true],
        'mastodon.analytics.report' => ['resource' => 'mastodon.analytics', 'counts' => false],
        'mastodon.parser.start' => ['resource' => 'mastodon.parser', 'counts' => true],
        'mastodon.parser.status' => ['resource' => 'mastodon.parser', 'counts' => false],
        'mastodon.parser.stop' => ['resource' => 'mastodon.parser', 'counts' => false],
        'mastodon.parser.download-excel' => ['resource' => 'mastodon.parser', 'counts' => false],
        'mastodon.parser.download-json' => ['resource' => 'mastodon.parser', 'counts' => false],
        'site-intel.analytics' => ['resource' => 'site-intel.analytics', 'counts' => true],
        'site-intel.report' => ['resource' => 'site-intel.analytics', 'counts' => false],
        'site-intel.seo-audit' => ['resource' => 'site-intel.seo-audit', 'counts' => true],
        'site-intel.seo-report' => ['resource' => 'site-intel.seo-audit', 'counts' => false],
        'telegram.analytics.summary' => ['resource' => 'telegram.analytics', 'counts' => true],
        'telegram.analytics.report' => ['resource' => 'telegram.analytics', 'counts' => false],
        'youtube.analytics.summary' => ['resource' => 'youtube.analytics', 'counts' => true],
        'youtube.analytics.report' => ['resource' => 'youtube.analytics', 'counts' => false],

        'telegram.parser.start' => ['resource' => 'telegram.parser', 'counts' => true],
        'telegram.parser.status' => ['resource' => 'telegram.parser', 'counts' => false],
        'telegram.parser.stop' => ['resource' => 'telegram.parser', 'counts' => false],
        'telegram.parser.download-excel' => ['resource' => 'telegram.parser', 'counts' => false],
        'telegram.parser.download-json' => ['resource' => 'telegram.parser', 'counts' => false],
        'youtube.parser.comments' => ['resource' => 'youtube.parser', 'counts' => true],
        'youtube.parser.start' => ['resource' => 'youtube.parser', 'counts' => true],
        'youtube.parser.status' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.stop' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.download-excel' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.download-json' => ['resource' => 'youtube.parser', 'counts' => false],
    ],
];
