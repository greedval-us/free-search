<?php

return [
    'plans' => [
        'free' => [
            'analytics' => 0,
            'parser' => 0,
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
            'site-intel.analytics' => 100,
            'site-intel.seo-audit' => 100,
            'telegram.analytics' => 100,
            'telegram.parser' => 50,
            'youtube.analytics' => 100,
            'youtube.parser' => 50,
        ],
    ],

    'resources' => [
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
