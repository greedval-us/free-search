<?php

return [
    'plans' => [
        'free' => [
            'analytics' => 0,
            'parser' => 0,
        ],
        'plus' => [
            'analytics' => 10,
            'parser' => 5,
        ],
        'pro' => [
            'analytics' => 100,
            'parser' => 50,
        ],
    ],

    'resources' => [
        'site-intel.analytics' => [
            'module' => 'site-intel',
            'capability' => 'analytics',
            'quota_key' => 'analytics',
        ],
        'site-intel.seo-audit' => [
            'module' => 'site-intel',
            'capability' => 'seo-audit',
            'quota_key' => 'analytics',
        ],
        'telegram.analytics' => [
            'module' => 'telegram',
            'capability' => 'analytics',
            'quota_key' => 'analytics',
        ],
        'telegram.parser' => [
            'module' => 'telegram',
            'capability' => 'parser',
            'quota_key' => 'parser',
        ],
        'youtube.analytics' => [
            'module' => 'youtube',
            'capability' => 'analytics',
            'quota_key' => 'analytics',
        ],
        'youtube.parser' => [
            'module' => 'youtube',
            'capability' => 'parser',
            'quota_key' => 'parser',
        ],
    ],

    'bypass_account_types' => [
        'admin',
        'moderator',
    ],

    'protected_routes' => [
        'site-intel.analytics' => ['resource' => 'site-intel.analytics', 'counts' => true],
        'site-intel.report' => ['resource' => 'site-intel.analytics', 'counts' => true],
        'site-intel.seo-audit' => ['resource' => 'site-intel.seo-audit', 'counts' => true],
        'site-intel.seo-report' => ['resource' => 'site-intel.seo-audit', 'counts' => true],
        'telegram.analytics.summary' => ['resource' => 'telegram.analytics', 'counts' => true],
        'telegram.analytics.report' => ['resource' => 'telegram.analytics', 'counts' => true],
        'youtube.analytics.summary' => ['resource' => 'youtube.analytics', 'counts' => true],
        'youtube.analytics.report' => ['resource' => 'youtube.analytics', 'counts' => true],

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
