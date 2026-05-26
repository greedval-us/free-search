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

    'bypass_account_types' => [
        'admin',
        'moderator',
    ],

    'protected_routes' => [
        'site-intel.analytics' => ['feature' => 'analytics', 'counts' => true],
        'site-intel.report' => ['feature' => 'analytics', 'counts' => true],
        'site-intel.seo-audit' => ['feature' => 'analytics', 'counts' => true],
        'site-intel.seo-report' => ['feature' => 'analytics', 'counts' => true],
        'telegram.analytics.summary' => ['feature' => 'analytics', 'counts' => true],
        'telegram.analytics.report' => ['feature' => 'analytics', 'counts' => true],
        'youtube.analytics.summary' => ['feature' => 'analytics', 'counts' => true],
        'youtube.analytics.report' => ['feature' => 'analytics', 'counts' => true],

        'telegram.parser.start' => ['feature' => 'parser', 'counts' => true],
        'telegram.parser.status' => ['feature' => 'parser', 'counts' => false],
        'telegram.parser.stop' => ['feature' => 'parser', 'counts' => false],
        'telegram.parser.download-excel' => ['feature' => 'parser', 'counts' => false],
        'telegram.parser.download-json' => ['feature' => 'parser', 'counts' => false],
        'youtube.parser.comments' => ['feature' => 'parser', 'counts' => true],
        'youtube.parser.start' => ['feature' => 'parser', 'counts' => true],
        'youtube.parser.status' => ['feature' => 'parser', 'counts' => false],
        'youtube.parser.stop' => ['feature' => 'parser', 'counts' => false],
        'youtube.parser.download-excel' => ['feature' => 'parser', 'counts' => false],
        'youtube.parser.download-json' => ['feature' => 'parser', 'counts' => false],
    ],
];
