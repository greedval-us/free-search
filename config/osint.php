<?php

return [
    // Политики ретраев для frontend API-запросов (по умолчанию и для отдельных endpoint'ов).
    'frontend_api_retry' => [
        'default' => [
            // Количество повторов при ошибке.
            'attempts' => (int) env('OSINT_FRONTEND_RETRY_DEFAULT_ATTEMPTS', 2),
            // Базовая задержка между повторами (мс).
            'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DEFAULT_BASE_DELAY_MS', 250),
            // Максимальная задержка между повторами (мс).
            'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DEFAULT_MAX_DELAY_MS', 1500),
            // Повторять ли при сетевой ошибке (обрыв, timeout и т.п.).
            'retry_on_network_error' => (bool) env('OSINT_FRONTEND_RETRY_DEFAULT_ON_NETWORK_ERROR', true),
            // HTTP-статусы, при которых допустим повтор.
            'retry_on_statuses' => [408, 425, 429, 500, 502, 503, 504],
        ],
        // Точечные переопределения ретраев для конкретных маршрутов.
        'endpoint_rules' => [
            [
                'path' => '/telegram/parser/status/',
                'methods' => ['GET'],
                'policy' => [
                    'attempts' => (int) env('OSINT_FRONTEND_RETRY_PARSER_STATUS_ATTEMPTS', 4),
                    'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_PARSER_STATUS_BASE_DELAY_MS', 120),
                    'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_PARSER_STATUS_MAX_DELAY_MS', 1200),
                ],
            ],
            [
                'path' => '/telegram/parser/start',
                'methods' => ['POST'],
                'policy' => [
                    'attempts' => 0,
                    'retry_on_network_error' => false,
                ],
            ],
            [
                'path' => '/telegram/parser/stop/',
                'methods' => ['POST'],
                'policy' => [
                    'attempts' => 0,
                    'retry_on_network_error' => false,
                ],
            ],
            [
                'path' => '/telegram/analytics/summary',
                'methods' => ['GET'],
                'policy' => [
                    'attempts' => (int) env('OSINT_FRONTEND_RETRY_TG_ANALYTICS_ATTEMPTS', 3),
                    'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_TG_ANALYTICS_BASE_DELAY_MS', 180),
                    'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_TG_ANALYTICS_MAX_DELAY_MS', 1200),
                ],
            ],
            [
                'path' => '/site-intel/seo-audit',
                'methods' => ['GET'],
                'policy' => [
                    'attempts' => (int) env('OSINT_FRONTEND_RETRY_SEO_AUDIT_ATTEMPTS', 1),
                    'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SEO_AUDIT_BASE_DELAY_MS', 300),
                    'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SEO_AUDIT_MAX_DELAY_MS', 1200),
                ],
            ],
            [
                'path' => '/site-intel/analytics',
                'methods' => ['GET'],
                'policy' => [
                    'attempts' => (int) env('OSINT_FRONTEND_RETRY_SITE_ANALYTICS_ATTEMPTS', 2),
                    'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_ANALYTICS_BASE_DELAY_MS', 250),
                    'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_ANALYTICS_MAX_DELAY_MS', 1200),
                ],
            ],
            [
                'path' => '/site-intel/site-health',
                'methods' => ['GET'],
                'policy' => [
                    'attempts' => (int) env('OSINT_FRONTEND_RETRY_SITE_HEALTH_ATTEMPTS', 2),
                    'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_HEALTH_BASE_DELAY_MS', 220),
                    'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_SITE_HEALTH_MAX_DELAY_MS', 1000),
                ],
            ],
            [
                'path' => '/site-intel/domain-lite',
                'methods' => ['GET'],
                'policy' => [
                    'attempts' => (int) env('OSINT_FRONTEND_RETRY_DOMAIN_LITE_ATTEMPTS', 2),
                    'base_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DOMAIN_LITE_BASE_DELAY_MS', 200),
                    'max_delay_ms' => (int) env('OSINT_FRONTEND_RETRY_DOMAIN_LITE_MAX_DELAY_MS', 1000),
                ],
            ],
        ],
    ],

    // Настройки формирования HTML-отчётов и имени файлов выгрузки.
    'reports' => [
        'generated_at_format' => 'd.m.Y H:i',
        'filename_timestamp_format' => 'Ymd-His',
        'download_content_type' => 'text/html; charset=UTF-8',
    ],

    // Общие HTTP-настройки FIO-модуля.
    'fio' => [
        'http' => [
            'user_agent' => env('OSINT_FIO_HTTP_USER_AGENT', 'FreeSearch-FIO/1.0'),
            'timeout_seconds' => (int) env('OSINT_FIO_HTTP_TIMEOUT', 12),
            'retry_attempts' => (int) env('OSINT_FIO_HTTP_RETRY_ATTEMPTS', 1),
            'retry_sleep_milliseconds' => (int) env('OSINT_FIO_HTTP_RETRY_SLEEP', 250),
        ],
    ],

    // Настройки модулей SiteIntel (HTTP-проверки, WHOIS).
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

    // Сетевые параметры для Username-модуля.
    'username' => [
        'http' => [
            'connect_timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_CONNECT_TIMEOUT', 6),
            'timeout_seconds' => (int) env('OSINT_USERNAME_HTTP_TIMEOUT', 8),
            'max_redirects' => (int) env('OSINT_USERNAME_HTTP_MAX_REDIRECTS', 5),
            'user_agent' => env('OSINT_USERNAME_HTTP_USER_AGENT', 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)'),
            'accept' => env('OSINT_USERNAME_HTTP_ACCEPT', 'text/html,application/xhtml+xml'),
        ],
    ],

    // Пороги и веса риск-оценки для CompanyIntel + шаблоны внешних OSINT-ссылок.
    'company_intel' => [
        'risk' => [
            // Пороговые значения финального score.
            'score_for_medium' => (int) env('OSINT_COMPANY_INTEL_SCORE_FOR_MEDIUM', 30),
            'score_for_high' => (int) env('OSINT_COMPANY_INTEL_SCORE_FOR_HIGH', 60),
            'weights' => [
                // Веса сигналов риска (чем выше, тем сильнее вклад в общий риск).
                'no_dns_resolution' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_NO_DNS_RESOLUTION', 20),
                'missing_spf' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_MISSING_SPF', 12),
                'missing_dmarc' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_MISSING_DMARC', 15),
                'missing_mx' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_MISSING_MX', 8),
                'weak_ns_redundancy' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_WEAK_NS_REDUNDANCY', 8),
                'missing_caa' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_MISSING_CAA', 6),
                'dnssec_not_enabled' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_DNSSEC_NOT_ENABLED', 6),
                'spf_not_strict' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_SPF_NOT_STRICT', 6),
                'dmarc_not_enforced' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_DMARC_NOT_ENFORCED', 10),
                'young_domain' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_YOUNG_DOMAIN', 14),
                'domain_expired' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_DOMAIN_EXPIRED', 25),
                'domain_expiring_soon' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_DOMAIN_EXPIRING_SOON', 12),
                'whois_unavailable' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_WHOIS_UNAVAILABLE', 10),
                'whois_privacy_redacted' => (int) env('OSINT_COMPANY_INTEL_WEIGHT_WHOIS_PRIVACY_REDACTED', 5),
            ],
        ],
        'links' => [
            // Шаблоны глобальных поисковых ссылок. Плейсхолдеры подставляются в сервисе.
            'global' => [
                'news_search' => 'https://news.google.com/search?q={query}',
                'reddit_mentions' => 'https://www.reddit.com/search/?q={query}',
                'github_search' => 'https://github.com/search?q={query}',
                'job_signals' => 'https://www.google.com/search?q={query_jobs}',
                'linkedin_search' => 'https://www.google.com/search?q={query_linkedin}',
                'opencorporates_search' => 'https://opencorporates.com/companies?q={query}',
                'wikidata_search' => 'https://www.wikidata.org/w/index.php?search={query}',
                'glassdoor_search' => 'https://www.google.com/search?q={query_glassdoor}',
                'crunchbase_search' => 'https://www.google.com/search?q={query_crunchbase}',
                'x_mentions' => 'https://www.google.com/search?q={query_x}',
                'youtube_mentions' => 'https://www.google.com/search?q={query_youtube}',
                'medium_mentions' => 'https://www.google.com/search?q={query_medium}',
                'paste_leaks_search' => 'https://www.google.com/search?q={query_paste}',
                'patents_search' => 'https://patents.google.com/?q={query}',
            ],
            // Шаблоны ссылок, завязанных на домен.
            'domain' => [
                'crtsh_history' => 'https://crt.sh/?q={domain}',
                'urlhaus_lookup' => 'https://urlhaus.abuse.ch/browse.php?search={domain}',
                'phishtank_lookup' => 'https://phishtank.org/phish_search.php?search={domain}&valid=y&active=y',
                'viewdns_whois' => 'https://viewdns.info/whois/?domain={domain}',
                'wayback_archive' => 'https://web.archive.org/web/*/{domain}',
                'securitytrails_dns' => 'https://securitytrails.com/domain/{domain}/history/dns',
                'dnstwister' => 'https://dnstwister.report/search?domain={domain}',
                'talos_reputation' => 'https://talosintelligence.com/reputation_center/lookup?search={domain}',
                'virustotal_domain' => 'https://www.virustotal.com/gui/domain/{domain}',
                'urlscan_domain' => 'https://urlscan.io/domain/{domain}',
                'otx_domain' => 'https://otx.alienvault.com/indicator/domain/{domain}',
                'threatcrowd_domain' => 'https://www.threatcrowd.org/domain.php?domain={domain}',
                'hibp_breaches_domain' => 'https://haveibeenpwned.com/DomainSearch?domain={domain}',
                'builtwith_profile' => 'https://builtwith.com/{domain}',
                'dnslytics_domain' => 'https://dnslytics.com/domain/{domain}',
                'netcraft_report' => 'https://sitereport.netcraft.com/?url=http://{domain}',
            ],
        ],
    ],

    // Настройки поиска и извлечения метаданных документов.
    // NewsMediaIntel settings (RSS + NewsAPI).
    'news_media_intel' => [
        'newsapi' => [
            'api_key' => env('OSINT_NEWSAPI_KEY', ''),
            'base_url' => env('OSINT_NEWSAPI_BASE_URL', 'https://newsapi.org/v2/everything'),
            'language' => env('OSINT_NEWSAPI_LANGUAGE', 'ru'),
            'page_size' => (int) env('OSINT_NEWSAPI_PAGE_SIZE', 30),
            'timeout_seconds' => (int) env('OSINT_NEWSAPI_TIMEOUT', 15),
        ],
    ],
    'document_intel' => [
        'http' => [
            'user_agent' => env('OSINT_DOCUMENT_INTEL_HTTP_USER_AGENT', 'FreeSearch-DocumentIntel/1.0'),
            'timeout_seconds' => (int) env('OSINT_DOCUMENT_INTEL_HTTP_TIMEOUT', 10),
        ],
        'discovery' => [
            // Лимиты поиска документов и размер скачиваемого файла.
            'max_documents' => (int) env('OSINT_DOCUMENT_INTEL_MAX_DOCUMENTS', 20),
            'max_file_size_bytes' => (int) env('OSINT_DOCUMENT_INTEL_MAX_FILE_SIZE_BYTES', 5000000),
            'extensions' => ['pdf', 'docx', 'xlsx', 'pptx'],
        ],
        'extraction' => [
            // Максимум артефактов каждого типа в ответе.
            'max_items_per_type' => (int) env('OSINT_DOCUMENT_INTEL_MAX_ITEMS_PER_TYPE', 15),
        ],
        'risk' => [
            'thresholds' => [
                'medium' => (int) env('OSINT_DOCUMENT_INTEL_RISK_MEDIUM', 30),
                'high' => (int) env('OSINT_DOCUMENT_INTEL_RISK_HIGH', 60),
            ],
            'weights' => [
                'author_exposed' => (int) env('OSINT_DOCUMENT_INTEL_RISK_AUTHOR_EXPOSED', 20),
                'email_exposed' => (int) env('OSINT_DOCUMENT_INTEL_RISK_EMAIL_EXPOSED', 15),
                'internal_paths_exposed' => (int) env('OSINT_DOCUMENT_INTEL_RISK_INTERNAL_PATHS_EXPOSED', 25),
                'legacy_software_hint' => (int) env('OSINT_DOCUMENT_INTEL_RISK_LEGACY_SOFTWARE_HINT', 15),
            ],
        ],
    ],

    // Настройки Telegram-аналитики: диапазоны, кэш, лимиты выборки, антифрод и scoring-профили.
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

