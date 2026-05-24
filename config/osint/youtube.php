<?php

return [
    'analytics_period_days' => [1, 3, 7],
    'analytics_default_period_days' => (int) env('OSINT_YOUTUBE_ANALYTICS_DEFAULT_PERIOD_DAYS', 7),
    'analytics_custom_range_max_days' => (int) env('OSINT_YOUTUBE_ANALYTICS_CUSTOM_RANGE_MAX_DAYS', 7),
    'parser_comments_limit_default' => (int) env('OSINT_YOUTUBE_PARSER_COMMENTS_LIMIT_DEFAULT', 20),
    'parser_comments_limit_max' => (int) env('OSINT_YOUTUBE_PARSER_COMMENTS_LIMIT_MAX', 100),
    'search_limit_default' => (int) env('OSINT_YOUTUBE_SEARCH_LIMIT_DEFAULT', 10),
    'search_limit_max' => (int) env('OSINT_YOUTUBE_SEARCH_LIMIT_MAX', 10),
];
