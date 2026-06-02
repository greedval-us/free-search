<?php

return [
    'search_limit_default' => (int) env('OSINT_MASTODON_SEARCH_LIMIT_DEFAULT', 10),
    'search_limit_max' => (int) env('OSINT_MASTODON_SEARCH_LIMIT_MAX', 20),
    'default_type' => env('OSINT_MASTODON_SEARCH_DEFAULT_TYPE', 'statuses'),
];
