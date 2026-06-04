<?php

return [
    'plans' => [
        'free' => [
            'analytics' => 0,
            'parser' => 0,
            'bluesky.analytics' => 0,
            'bluesky.parser' => 0,
            'mastodon.analytics' => 0,
            'mastodon.parser' => 0,
            'site-intel.analytics' => 0,
            'site-intel.seo-audit' => 0,
            'telegram.analytics' => 0,
            'telegram.parser' => 0,
            'youtube.analytics' => 0,
            'youtube.parser' => 0,
        ],
        'plus' => [
            'analytics' => 10,
            'parser' => 5,
            'bluesky.analytics' => 10,
            'bluesky.parser' => 5,
            'mastodon.analytics' => 10,
            'mastodon.parser' => 5,
            'site-intel.analytics' => 10,
            'site-intel.seo-audit' => 10,
            'telegram.analytics' => 10,
            'telegram.parser' => 5,
            'youtube.analytics' => 10,
            'youtube.parser' => 5,
        ],
        'pro' => [
            'analytics' => 100,
            'parser' => 50,
            'bluesky.analytics' => 100,
            'bluesky.parser' => 50,
            'mastodon.analytics' => 100,
            'mastodon.parser' => 50,
            'site-intel.analytics' => 100,
            'site-intel.seo-audit' => 100,
            'telegram.analytics' => 100,
            'telegram.parser' => 50,
            'youtube.analytics' => 100,
            'youtube.parser' => 50,
        ],
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
        'youtube.search.comments-preview' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.comments' => ['resource' => 'youtube.parser', 'counts' => true],
        'youtube.parser.start' => ['resource' => 'youtube.parser', 'counts' => true],
        'youtube.parser.status' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.stop' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.download-excel' => ['resource' => 'youtube.parser', 'counts' => false],
        'youtube.parser.download-json' => ['resource' => 'youtube.parser', 'counts' => false],
    ],
];
