<?php

return [
    'search_limit_default' => (int) env('OSINT_BLUESKY_SEARCH_LIMIT_DEFAULT', 10),
    'search_limit_max' => (int) env('OSINT_BLUESKY_SEARCH_LIMIT_MAX', 25),
    'default_type' => env('OSINT_BLUESKY_SEARCH_DEFAULT_TYPE', 'posts'),
    'default_sort' => env('OSINT_BLUESKY_SEARCH_DEFAULT_SORT', 'latest'),
];
