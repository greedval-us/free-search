<?php

return [
    'service' => [
        'max_mentions' => (int) env('OSINT_NEWS_MEDIA_MAX_MENTIONS', 120),
    ],
    'searxng' => [
        'base_url' => env('OSINT_NEWS_MEDIA_SEARXNG_BASE_URL', 'http://127.0.0.1:8088'),
        'language' => env('OSINT_NEWS_MEDIA_SEARXNG_LANGUAGE', 'ru'),
        'engines' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('OSINT_NEWS_MEDIA_SEARXNG_ENGINES', ''))
        ))),
        'max_pages' => (int) env('OSINT_NEWS_MEDIA_SEARXNG_MAX_PAGES', 3),
        'timeout_seconds' => (int) env('OSINT_NEWS_MEDIA_SEARXNG_TIMEOUT', 10),
        'request_budget_seconds' => (int) env('OSINT_NEWS_MEDIA_SEARXNG_REQUEST_BUDGET', 20),
        'safe_search' => (int) env('OSINT_NEWS_MEDIA_SEARXNG_SAFESEARCH', 1),
        'time_range' => env('OSINT_NEWS_MEDIA_SEARXNG_TIME_RANGE', ''),
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
