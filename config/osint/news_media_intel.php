<?php

return [
    'service' => [
        'max_mentions' => (int) env('OSINT_NEWS_MEDIA_MAX_MENTIONS', 120),
    ],
    'fetcher' => [
        'per_provider_limit' => (int) env('OSINT_NEWS_MEDIA_FETCHER_PER_PROVIDER_LIMIT', 40),
        'provider_order' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('OSINT_NEWS_MEDIA_FETCHER_PROVIDER_ORDER', 'newsapi,googlenews,bing'))
        ))),
    ],
    'rss' => [
        'timeout_seconds' => (int) env('OSINT_NEWS_MEDIA_RSS_TIMEOUT', 15),
        'accept' => env('OSINT_NEWS_MEDIA_RSS_ACCEPT', 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8'),
        'bing' => [
            'url_template' => env('OSINT_NEWS_MEDIA_RSS_BING_URL_TEMPLATE', 'https://www.bing.com/search?format=rss&q={query}'),
        ],
        'google' => [
            'url_template' => env(
                'OSINT_NEWS_MEDIA_RSS_GOOGLE_URL_TEMPLATE',
                'https://news.google.com/rss/search?q={query}&hl={hl}&gl={gl}&ceid={ceid}'
            ),
            'hl' => env('OSINT_NEWS_MEDIA_RSS_GOOGLE_HL', 'ru'),
            'gl' => env('OSINT_NEWS_MEDIA_RSS_GOOGLE_GL', 'RU'),
            'ceid' => env('OSINT_NEWS_MEDIA_RSS_GOOGLE_CEID', 'RU:ru'),
        ],
    ],
    'newsapi' => [
        'api_key' => env('OSINT_NEWSAPI_KEY', ''),
        'base_url' => env('OSINT_NEWSAPI_BASE_URL', 'https://newsapi.org/v2/everything'),
        'language' => env('OSINT_NEWSAPI_LANGUAGE', 'ru'),
        'page_size' => (int) env('OSINT_NEWSAPI_PAGE_SIZE', 30),
        'timeout_seconds' => (int) env('OSINT_NEWSAPI_TIMEOUT', 15),
    ],
    'analysis' => [
        'sentiment' => [
            'positive_words' => [
                'success', 'growth', 'win', 'award', 'profit', 'improve', 'improved', 'improvement', 'recovery',
                'успех', 'рост', 'прибыль', 'улучш', 'восстанов', 'рекорд', 'развит',
            ],
            'negative_words' => [
                'fraud', 'crime', 'attack', 'breach', 'loss', 'scandal', 'sanction', 'lawsuit', 'hack', 'leak',
                'мошенн', 'преступ', 'атака', 'утеч', 'скандал', 'санкц', 'убыт', 'взлом', 'иск', 'коррупц',
            ],
        ],
        'topics' => [
            'stop_words' => [
                'the', 'and', 'with', 'from', 'that', 'this', 'for', 'about', 'into', 'over', 'after', 'before',
                'или', 'как', 'что', 'это', 'при', 'после', 'если', 'когда', 'чтобы', 'под', 'над',
            ],
            'min_word_length' => (int) env('OSINT_NEWS_MEDIA_TOPICS_MIN_WORD_LENGTH', 4),
            'top_limit' => (int) env('OSINT_NEWS_MEDIA_TOPICS_TOP_LIMIT', 20),
        ],
    ],
    'deduplication' => [
        'strip_www' => filter_var(env('OSINT_NEWS_MEDIA_DEDUP_STRIP_WWW', true), FILTER_VALIDATE_BOOL),
        'trim_trailing_slash' => filter_var(env('OSINT_NEWS_MEDIA_DEDUP_TRIM_TRAILING_SLASH', true), FILTER_VALIDATE_BOOL),
        'query_trackers' => [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'yclid',
        ],
    ],
];
