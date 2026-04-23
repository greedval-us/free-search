<?php

return [
    'reports' => [
        'generated_at_format' => 'd.m.Y H:i',
        'filename_timestamp_format' => 'Ymd-His',
        'download_content_type' => 'text/html; charset=UTF-8',
    ],

    'fio' => [
        'http' => [
            'user_agent' => env('OSINT_FIO_HTTP_USER_AGENT', 'FreeSearch-FIO/1.0'),
            'timeout_seconds' => (int) env('OSINT_FIO_HTTP_TIMEOUT', 12),
            'retry_attempts' => (int) env('OSINT_FIO_HTTP_RETRY_ATTEMPTS', 1),
            'retry_sleep_milliseconds' => (int) env('OSINT_FIO_HTTP_RETRY_SLEEP', 250),
        ],
    ],

    'site_intel' => [
        'http' => [
            'user_agent' => env('OSINT_SITE_HEALTH_HTTP_USER_AGENT', 'FreeSearch-SiteHealth/1.0'),
            'accept' => env('OSINT_SITE_HEALTH_HTTP_ACCEPT', '*/*'),
            'timeout_seconds' => (int) env('OSINT_SITE_HEALTH_HTTP_TIMEOUT', 10),
            'max_redirects' => (int) env('OSINT_SITE_HEALTH_HTTP_MAX_REDIRECTS', 5),
            'verify_ssl' => (bool) env('OSINT_SITE_HEALTH_HTTP_VERIFY_SSL', false),
        ],
        'whois' => [
            'iana_server' => env('OSINT_SITE_INTEL_WHOIS_IANA_SERVER', 'whois.iana.org'),
            'connect_timeout_seconds' => (int) env('OSINT_SITE_INTEL_WHOIS_CONNECT_TIMEOUT', 8),
            'read_timeout_seconds' => (int) env('OSINT_SITE_INTEL_WHOIS_READ_TIMEOUT', 8),
            'read_chunk_size' => (int) env('OSINT_SITE_INTEL_WHOIS_READ_CHUNK_SIZE', 2048),
            'max_response_bytes' => (int) env('OSINT_SITE_INTEL_WHOIS_MAX_RESPONSE_BYTES', 120000),
        ],
    ],

    'username' => [
        'http' => [
            'connect_timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_CONNECT_TIMEOUT', 6),
            'timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_TIMEOUT', 8),
            'max_redirects' => (int) env('OSINT_USERNAME_HTTP_MAX_REDIRECTS', 5),
            'user_agent' => env('OSINT_USERNAME_HTTP_USER_AGENT', 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)'),
            'accept' => env('OSINT_USERNAME_HTTP_ACCEPT', 'text/html,application/xhtml+xml'),
        ],
    ],

    'dorks' => [
        'http' => [
            'connect_timeout_seconds' => (int) env('OSINT_DORKS_HTTP_CONNECT_TIMEOUT', 8),
            'timeout_seconds' => (int) env('OSINT_DORKS_HTTP_TIMEOUT', 12),
            'user_agent' => env('OSINT_DORKS_HTTP_USER_AGENT', 'FreeSearch-Dorks/1.0'),
            'accept' => env('OSINT_DORKS_HTTP_ACCEPT', 'text/html,application/xhtml+xml,application/xml'),
        ],
        'search' => [
            'max_results' => (int) env('OSINT_DORKS_MAX_RESULTS', 120),
            'per_provider_limit' => (int) env('OSINT_DORKS_PER_PROVIDER_LIMIT', 20),
            'dorks_per_goal' => (int) env('OSINT_DORKS_DORKS_PER_GOAL', 3),
            'default_goal' => env('OSINT_DORKS_DEFAULT_GOAL', 'all'),
            'max_target_length' => (int) env('OSINT_DORKS_MAX_TARGET_LENGTH', 120),
        ],
        'sources' => [
            'bing' => ['enabled' => (bool) env('OSINT_DORKS_SOURCE_BING', true)],
            'duckduckgo' => ['enabled' => (bool) env('OSINT_DORKS_SOURCE_DUCKDUCKGO', true)],
            'reddit' => ['enabled' => (bool) env('OSINT_DORKS_SOURCE_REDDIT', true)],
        ],
        'goals' => [
            'documents' => [
                'label' => 'Documents',
                'templates' => [
                    'site:{target} (filetype:pdf OR filetype:doc OR filetype:docx OR filetype:xls OR filetype:xlsx OR filetype:csv)',
                    '"{target}" "report" filetype:pdf',
                    '"{target}" "statistics" filetype:pdf',
                ],
            ],
            'brand_mentions' => [
                'label' => 'Brand Mentions',
                'templates' => [
                    '"{target}"',
                    '"{target}" review OR opinion',
                    '"{target}" "case study"',
                ],
            ],
            'competitor_intel' => [
                'label' => 'Competitor Intel',
                'templates' => [
                    '"{target}" "our clients"',
                    '"{target}" "customers include"',
                    '"{target}" "portfolio"',
                ],
            ],
            'discussions' => [
                'label' => 'Discussions',
                'templates' => [
                    'site:reddit.com "{target}"',
                    'site:stackoverflow.com "{target}"',
                    'site:quora.com "{target}"',
                ],
            ],
            'news_trends' => [
                'label' => 'News and Trends',
                'templates' => [
                    '"{target}" "trend"',
                    '"{target}" "market"',
                    '"{target}" "2025" OR "2026"',
                ],
            ],
            'jobs_market' => [
                'label' => 'Jobs and Hiring',
                'templates' => [
                    '"{target}" "requirements"',
                    '"{target}" "job description"',
                    'site:linkedin.com/jobs "{target}"',
                ],
            ],
            'api_tech' => [
                'label' => 'API and Tech',
                'templates' => [
                    '"{target}" api documentation',
                    'site:github.com "{target}"',
                    'site:github.com "parser" "{target}"',
                ],
            ],
            'datasets' => [
                'label' => 'Datasets',
                'templates' => [
                    '"{target}" "dataset"',
                    'filetype:csv "{target}"',
                    'filetype:xlsx "{target}"',
                ],
            ],
            'security_exposure' => [
                'label' => 'Security Exposure',
                'templates' => [
                    'site:{target} intitle:"index of" "backup"',
                    'site:{target} filetype:log "error"',
                    'site:{target} ("swagger-ui" OR "openapi.json" OR "/actuator")',
                ],
            ],
        ],
    ],

    'telegram' => [
        'analytics' => [
            'period_min_days' => (int) env('OSINT_TELEGRAM_ANALYTICS_PERIOD_MIN_DAYS', 1),
            'period_max_days' => (int) env('OSINT_TELEGRAM_ANALYTICS_PERIOD_MAX_DAYS', 7),
            'custom_range_max_days' => (int) env('OSINT_TELEGRAM_ANALYTICS_CUSTOM_RANGE_MAX_DAYS', 7),
            'summary_cache_ttl_seconds' => (int) env('OSINT_TELEGRAM_ANALYTICS_SUMMARY_CACHE_TTL', 60),
            'group_by_hour_threshold_hours' => (int) env('OSINT_TELEGRAM_ANALYTICS_GROUP_BY_HOUR_THRESHOLD_HOURS', 36),
            'top_posts_limit' => (int) env('OSINT_TELEGRAM_ANALYTICS_TOP_POSTS_LIMIT', 5),
            'top_distribution_limit' => (int) env('OSINT_TELEGRAM_ANALYTICS_TOP_DISTRIBUTION_LIMIT', 5),
            'fetch' => [
                'max_pages' => (int) env('OSINT_TELEGRAM_ANALYTICS_FETCH_MAX_PAGES', 20),
                'page_limit' => (int) env('OSINT_TELEGRAM_ANALYTICS_FETCH_PAGE_LIMIT', 100),
            ],
            'audience' => [
                'top_authors_share_limit' => (int) env('OSINT_TELEGRAM_ANALYTICS_AUDIENCE_TOP_AUTHORS_SHARE_LIMIT', 5),
                'most_active_hours_limit' => (int) env('OSINT_TELEGRAM_ANALYTICS_AUDIENCE_MOST_ACTIVE_HOURS_LIMIT', 3),
                'hour_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_AUDIENCE_HOUR_MIN', 0),
                'hour_max' => (int) env('OSINT_TELEGRAM_ANALYTICS_AUDIENCE_HOUR_MAX', 23),
            ],
            'fraud' => [
                'suspicious_post_excerpt_length' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_SUSPICIOUS_POST_EXCERPT_LENGTH', 160),
                'suspicious_posts_limit' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_SUSPICIOUS_POSTS_LIMIT', 5),
                'risk_max_score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RISK_MAX_SCORE', 100),
                'risk_medium_threshold' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RISK_MEDIUM_THRESHOLD', 30),
                'risk_high_threshold' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RISK_HIGH_THRESHOLD', 60),
                'post_rules' => [
                    'interactions_without_views' => [
                        'interactions_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_INTERACTIONS_WITHOUT_VIEWS_INTERACTIONS_MIN', 5),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_INTERACTIONS_WITHOUT_VIEWS_SCORE', 35),
                    ],
                    'high_reaction_ratio' => [
                        'reactions_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_REACTION_RATIO_REACTIONS_MIN', 20),
                        'ratio_min' => (float) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_REACTION_RATIO_RATIO_MIN', 0.7),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_REACTION_RATIO_SCORE', 30),
                    ],
                    'high_forward_ratio' => [
                        'forwards_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_FORWARD_RATIO_FORWARDS_MIN', 15),
                        'ratio_min' => (float) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_FORWARD_RATIO_RATIO_MIN', 0.5),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_FORWARD_RATIO_SCORE', 25),
                    ],
                    'gifts_with_low_views' => [
                        'views_max_exclusive' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_GIFTS_WITH_LOW_VIEWS_VIEWS_MAX', 10),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_GIFTS_WITH_LOW_VIEWS_SCORE', 20),
                    ],
                    'high_interactions_low_views' => [
                        'interactions_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_INTERACTIONS_LOW_VIEWS_INTERACTIONS_MIN', 50),
                        'views_max_exclusive' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_INTERACTIONS_LOW_VIEWS_VIEWS_MAX', 30),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_HIGH_INTERACTIONS_LOW_VIEWS_SCORE', 10),
                    ],
                    'suspicious_post_min_risk_score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_RULE_SUSPICIOUS_POST_MIN_RISK_SCORE', 30),
                ],
                'triggers' => [
                    'zero_view_interactions' => [
                        'threshold' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_ZERO_VIEW_INTERACTIONS_THRESHOLD', 3),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_ZERO_VIEW_INTERACTIONS_SCORE', 30),
                    ],
                    'author_concentration' => [
                        'messages_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_AUTHOR_CONCENTRATION_MESSAGES_MIN', 20),
                        'top_author_share_min' => (float) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_AUTHOR_CONCENTRATION_SHARE_MIN', 60.0),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_AUTHOR_CONCENTRATION_SCORE', 20),
                    ],
                    'time_burst' => [
                        'messages_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_TIME_BURST_MESSAGES_MIN', 20),
                        'burst_share_min' => (float) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_TIME_BURST_SHARE_MIN', 45.0),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_TIME_BURST_SCORE', 20),
                    ],
                    'reaction_ratio_cluster' => [
                        'views_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_REACTION_RATIO_CLUSTER_VIEWS_MIN', 20),
                        'reactions_min' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_REACTION_RATIO_CLUSTER_REACTIONS_MIN', 10),
                        'ratio_min' => (float) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_REACTION_RATIO_CLUSTER_RATIO_MIN', 0.5),
                        'threshold' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_REACTION_RATIO_CLUSTER_THRESHOLD', 3),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_REACTION_RATIO_CLUSTER_SCORE', 15),
                    ],
                    'suspicious_posts_cluster' => [
                        'threshold' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_SUSPICIOUS_POSTS_CLUSTER_THRESHOLD', 3),
                        'score' => (int) env('OSINT_TELEGRAM_ANALYTICS_FRAUD_TRIGGER_SUSPICIOUS_POSTS_CLUSTER_SCORE', 15),
                    ],
                ],
            ],
            'score_profiles' => [
                'balanced' => [
                    'views' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_BALANCED_VIEWS', 1.0),
                    'forwards' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_BALANCED_FORWARDS', 5.0),
                    'replies' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_BALANCED_REPLIES', 8.0),
                    'reactions' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_BALANCED_REACTIONS', 2.0),
                    'gifts' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_BALANCED_GIFTS', 10.0),
                ],
                'reach' => [
                    'views' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_REACH_VIEWS', 3.0),
                    'forwards' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_REACH_FORWARDS', 4.0),
                    'replies' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_REACH_REPLIES', 2.0),
                    'reactions' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_REACH_REACTIONS', 1.0),
                    'gifts' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_REACH_GIFTS', 2.0),
                ],
                'discussion' => [
                    'views' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_DISCUSSION_VIEWS', 1.0),
                    'forwards' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_DISCUSSION_FORWARDS', 3.0),
                    'replies' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_DISCUSSION_REPLIES', 12.0),
                    'reactions' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_DISCUSSION_REACTIONS', 2.0),
                    'gifts' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_DISCUSSION_GIFTS', 3.0),
                ],
                'virality' => [
                    'views' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_VIRALITY_VIEWS', 1.0),
                    'forwards' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_VIRALITY_FORWARDS', 10.0),
                    'replies' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_VIRALITY_REPLIES', 6.0),
                    'reactions' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_VIRALITY_REACTIONS', 3.0),
                    'gifts' => (float) env('OSINT_TELEGRAM_ANALYTICS_SCORE_VIRALITY_GIFTS', 5.0),
                ],
            ],
        ],
        'search' => [
            'messages_limit_default' => (int) env('OSINT_TELEGRAM_MESSAGES_LIMIT_DEFAULT', 20),
            'messages_limit_max' => (int) env('OSINT_TELEGRAM_MESSAGES_LIMIT_MAX', 100),
            'comments_limit_default' => (int) env('OSINT_TELEGRAM_COMMENTS_LIMIT_DEFAULT', 20),
            'comments_limit_max' => (int) env('OSINT_TELEGRAM_COMMENTS_LIMIT_MAX', 50),
        ],
        'parser' => [
            'custom_range_max_days' => (int) env('OSINT_TELEGRAM_PARSER_CUSTOM_RANGE_MAX_DAYS', 31),
        ],
    ],
];
