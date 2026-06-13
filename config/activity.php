<?php

return [
    /**
     * Declarative mapping for rebuilding module run URLs from request logs.
     *
     * Supported param specs:
     * - string literal
     * - ['keys' => ['payloadKey1', 'payloadKey2']]
     * - ['int' => 'payloadKey']
     */
    'run_url_routes' => [
        '/site-intel/site-health' => [
            'base' => '/site-intel',
            'params' => [
                'tab' => 'siteHealth',
                'target' => ['keys' => ['target']],
                'autorun' => '1',
            ],
        ],
        '/site-intel/domain-lite' => [
            'base' => '/site-intel',
            'params' => [
                'tab' => 'domainLite',
                'domain' => ['keys' => ['domain']],
                'autorun' => '1',
            ],
        ],
        '/site-intel/analytics' => [
            'base' => '/site-intel',
            'params' => [
                'tab' => 'analytics',
                'target' => ['keys' => ['target']],
                'autorun' => '1',
            ],
        ],
        '/site-intel/report' => [
            'base' => '/site-intel',
            'params' => [
                'tab' => 'analytics',
                'target' => ['keys' => ['target']],
                'autorun' => '1',
            ],
        ],
        '/telegram/search/messages' => [
            'base' => '/telegram',
            'params' => [
                'tab' => 'search',
                'chatUsername' => ['keys' => ['chatUsername']],
                'q' => ['keys' => ['q']],
                'fromUsername' => ['keys' => ['fromUsername']],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'limit' => ['int' => 'limit'],
                'autorun' => '1',
            ],
        ],
        '/telegram/search/comments' => [
            'base' => '/telegram',
            'params' => [
                'tab' => 'search',
                'chatUsername' => ['keys' => ['chatUsername']],
                'q' => ['keys' => ['q']],
                'fromUsername' => ['keys' => ['fromUsername']],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'limit' => ['int' => 'limit'],
                'autorun' => '1',
            ],
        ],
        '/telegram/analytics/summary' => [
            'base' => '/telegram',
            'params' => [
                'tab' => 'analytics',
                'chatUsername' => ['keys' => ['chatUsername']],
                'keyword' => ['keys' => ['keyword']],
                'periodDays' => ['int' => 'periodDays'],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'scorePriority' => ['keys' => ['scorePriority']],
                'autorun' => '1',
            ],
        ],
        '/telegram/analytics/report' => [
            'base' => '/telegram',
            'params' => [
                'tab' => 'analytics',
                'chatUsername' => ['keys' => ['chatUsername']],
                'keyword' => ['keys' => ['keyword']],
                'periodDays' => ['int' => 'periodDays'],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'scorePriority' => ['keys' => ['scorePriority']],
                'autorun' => '1',
            ],
        ],
        '/youtube/search/videos' => [
            'base' => '/youtube',
            'params' => [
                'tab' => 'search',
                'q' => ['keys' => ['q']],
                'type' => ['keys' => ['type']],
                'channelId' => ['keys' => ['channelId']],
                'order' => ['keys' => ['order']],
                'publishedAfter' => ['keys' => ['publishedAfter']],
                'publishedBefore' => ['keys' => ['publishedBefore']],
                'regionCode' => ['keys' => ['regionCode']],
                'relevanceLanguage' => ['keys' => ['relevanceLanguage']],
                'safeSearch' => ['keys' => ['safeSearch']],
                'videoDuration' => ['keys' => ['videoDuration']],
                'videoDefinition' => ['keys' => ['videoDefinition']],
                'videoCaption' => ['keys' => ['videoCaption']],
                'limit' => ['int' => 'limit'],
                'autorun' => '1',
            ],
        ],
        '/youtube/analytics/summary' => [
            'base' => '/youtube',
            'params' => [
                'tab' => 'analytics',
                'mode' => ['keys' => ['mode']],
                'videoId' => ['keys' => ['videoId']],
                'channelId' => ['keys' => ['channelId']],
                'periodDays' => ['int' => 'periodDays'],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'autorun' => '1',
            ],
        ],
        '/youtube/analytics/report' => [
            'base' => '/youtube',
            'params' => [
                'tab' => 'analytics',
                'mode' => ['keys' => ['mode']],
                'videoId' => ['keys' => ['videoId']],
                'channelId' => ['keys' => ['channelId']],
                'periodDays' => ['int' => 'periodDays'],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'autorun' => '1',
            ],
        ],
        '/youtube/parser/comments' => [
            'base' => '/youtube',
            'params' => [
                'tab' => 'parser',
                'videoId' => ['keys' => ['videoId']],
                'order' => ['keys' => ['order']],
                'searchTerms' => ['keys' => ['searchTerms']],
                'limit' => ['int' => 'limit'],
                'autorun' => '1',
            ],
        ],
        '/mastodon/search' => [
            'base' => '/mastodon',
            'params' => [
                'tab' => 'search',
                'q' => ['keys' => ['q']],
                'type' => ['keys' => ['type']],
                'limit' => ['int' => 'limit'],
                'resolve' => ['keys' => ['resolve']],
                'author' => ['keys' => ['author']],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'language' => ['keys' => ['language']],
                'hasMedia' => ['keys' => ['hasMedia']],
                'hasLinks' => ['keys' => ['hasLinks']],
                'hasReplies' => ['keys' => ['hasReplies']],
                'instanceDomain' => ['keys' => ['instanceDomain']],
                'autorun' => '1',
            ],
        ],
        '/mastodon/analytics/summary' => [
            'base' => '/mastodon',
            'params' => [
                'tab' => 'analytics',
                'mode' => ['keys' => ['mode']],
                'target' => ['keys' => ['target']],
                'limit' => ['int' => 'limit'],
                'pages' => ['int' => 'pages'],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'resolve' => ['keys' => ['resolve']],
                'autorun' => '1',
            ],
        ],
        '/bluesky/search' => [
            'base' => '/bluesky',
            'params' => [
                'tab' => 'search',
                'q' => ['keys' => ['q']],
                'type' => ['keys' => ['type']],
                'limit' => ['int' => 'limit'],
                'sort' => ['keys' => ['sort']],
                'language' => ['keys' => ['language']],
                'author' => ['keys' => ['author']],
                'mentions' => ['keys' => ['mentions']],
                'domain' => ['keys' => ['domain']],
                'url' => ['keys' => ['url']],
                'tag' => ['keys' => ['tag']],
                'since' => ['keys' => ['since']],
                'until' => ['keys' => ['until']],
                'autorun' => '1',
            ],
        ],
        '/bluesky/analytics/summary' => [
            'base' => '/bluesky',
            'params' => [
                'tab' => 'analytics',
                'mode' => ['keys' => ['mode']],
                'target' => ['keys' => ['target']],
                'limit' => ['int' => 'limit'],
                'pages' => ['int' => 'pages'],
                'dateFrom' => ['keys' => ['dateFrom']],
                'dateTo' => ['keys' => ['dateTo']],
                'resolve' => ['keys' => ['resolve']],
                'autorun' => '1',
            ],
        ],
        '/shifr/hash' => [
            'base' => '/shifr',
            'params' => [
                'tab' => 'hash',
                'text' => ['keys' => ['text']],
                'algorithm' => ['keys' => ['algorithm']],
                'hmac_key' => ['keys' => ['hmac_key']],
                'autorun' => '1',
            ],
        ],
        '/shifr/transform' => [
            'base' => '/shifr',
            'params' => [
                'tab' => 'transform',
                'text' => ['keys' => ['text']],
                'operation' => ['keys' => ['operation']],
                'autorun' => '1',
            ],
        ],
        '/shifr/ioc-extract' => [
            'base' => '/shifr',
            'params' => [
                'tab' => 'ioc',
                'text' => ['keys' => ['text']],
                'autorun' => '1',
            ],
        ],
        '/shifr/jwt-inspect' => [
            'base' => '/shifr',
            'params' => [
                'tab' => 'jwt',
                'token' => ['keys' => ['token']],
                'secret' => ['keys' => ['secret']],
                'autorun' => '1',
            ],
        ],
        '/shifr/classic' => [
            'base' => '/shifr',
            'params' => [
                'tab' => 'classic',
                'text' => ['keys' => ['text']],
                'cipher' => ['keys' => ['cipher']],
                'direction' => ['keys' => ['direction']],
                'shift' => ['int' => 'shift'],
                'key' => ['keys' => ['key']],
                'rails' => ['int' => 'rails'],
                'xor_key' => ['keys' => ['xor_key']],
                'affine_a' => ['int' => 'affine_a'],
                'affine_b' => ['int' => 'affine_b'],
                'playfair_key' => ['keys' => ['playfair_key']],
                'column_key' => ['keys' => ['column_key']],
                'morse_separator' => ['keys' => ['morse_separator']],
                'autorun' => '1',
            ],
        ],
    ],
];
