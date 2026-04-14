<?php

return [
    'cache' => [
        'search_ttl_seconds' => 300,
        'similarity_ttl_seconds' => 300,
    ],

    'analytics' => [
        'similarity' => [
            'max_variants' => 8,
            'deep_check_variants' => 3,
            'priority_source_keys' => [
                'telegram',
                'github',
                'instagram',
                'x',
                'vk',
                'reddit',
            ],
            'rules' => [
                'separators' => ['_', '.', '-'],
                'prefixes' => ['real', 'official', 'the'],
                'suffixes' => ['official', 'dev', 'team', 'hq'],
                'numeric_suffixes' => ['1', '01', '24', '2026'],
                'leet_substitutions' => [
                    'a' => '4',
                    'e' => '3',
                    'i' => '1',
                    'o' => '0',
                    's' => '5',
                ],
            ],
        ],
    ],

    'request' => [
        'connect_timeout' => 6,
        'timeout' => 8,
        'max_redirects' => 5,
        'user_agent' => 'Mozilla/5.0 (compatible; UraborosOSINT/1.0; +https://localhost)',
    ],

    'sources' => [
        // CIS
        [
            'key' => 'vk',
            'name' => 'VK',
            'profile_template' => 'https://vk.com/%s',
            'region_group' => 'cis',
            'primary_users_region' => 'cis',
        ],
        [
            'key' => 'habr',
            'name' => 'Habr',
            'profile_template' => 'https://habr.com/ru/users/%s/',
            'region_group' => 'cis',
            'primary_users_region' => 'cis',
            'not_found_markers' => [
                'Страница не найдена',
                'Page not found',
            ],
        ],
        [
            'key' => 'pikabu',
            'name' => 'Pikabu',
            'profile_template' => 'https://pikabu.ru/@%s',
            'region_group' => 'cis',
            'primary_users_region' => 'cis',
        ],
        [
            'key' => 'rutube',
            'name' => 'RuTube',
            'profile_template' => 'https://rutube.ru/u/%s/',
            'region_group' => 'cis',
            'primary_users_region' => 'cis',
        ],
        [
            'key' => 'boosty',
            'name' => 'Boosty',
            'profile_template' => 'https://boosty.to/%s',
            'region_group' => 'cis',
            'primary_users_region' => 'cis',
        ],
        [
            'key' => 'telegram',
            'name' => 'Telegram',
            'profile_template' => 'https://t.me/%s',
            'region_group' => 'cis',
            'primary_users_region' => 'cis',
            'not_found_markers' => [
                'If you have Telegram, you can contact',
            ],
        ],

        // Europe
        [
            'key' => 'gitlab',
            'name' => 'GitLab',
            'profile_template' => 'https://gitlab.com/%s',
            'region_group' => 'europe',
            'primary_users_region' => 'europe',
        ],
        [
            'key' => 'codeberg',
            'name' => 'Codeberg',
            'profile_template' => 'https://codeberg.org/%s',
            'region_group' => 'europe',
            'primary_users_region' => 'europe',
        ],
        [
            'key' => 'mastodon_social',
            'name' => 'Mastodon Social',
            'profile_template' => 'https://mastodon.social/@%s',
            'region_group' => 'europe',
            'primary_users_region' => 'europe',
        ],
        [
            'key' => 'lastfm',
            'name' => 'Last.fm',
            'profile_template' => 'https://www.last.fm/user/%s',
            'region_group' => 'europe',
            'primary_users_region' => 'europe',
        ],
        [
            'key' => 'xing',
            'name' => 'XING',
            'profile_template' => 'https://www.xing.com/profile/%s',
            'region_group' => 'europe',
            'primary_users_region' => 'europe',
            'strict_profile_uri' => true,
        ],

        // Americas
        [
            'key' => 'github',
            'name' => 'GitHub',
            'profile_template' => 'https://github.com/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'reddit',
            'name' => 'Reddit',
            'profile_template' => 'https://www.reddit.com/user/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'devto',
            'name' => 'DEV Community',
            'profile_template' => 'https://dev.to/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
            'not_found_markers' => [
                '404: This page could not be found',
            ],
        ],
        [
            'key' => 'huggingface',
            'name' => 'Hugging Face',
            'profile_template' => 'https://huggingface.co/%s',
        ],
        [
            'key' => 'medium',
            'name' => 'Medium',
            'profile_template' => 'https://medium.com/@%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
            'not_found_markers' => [
                'Page not found',
            ],
        ],
        [
            'key' => 'npm',
            'name' => 'npm',
            'profile_template' => 'https://www.npmjs.com/~%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'dockerhub',
            'name' => 'Docker Hub',
            'profile_template' => 'https://hub.docker.com/u/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'pypi',
            'name' => 'PyPI',
            'profile_template' => 'https://pypi.org/user/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'keybase',
            'name' => 'Keybase',
            'profile_template' => 'https://keybase.io/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'stackoverflow',
            'name' => 'Stack Overflow',
            'profile_template' => 'https://stackoverflow.com/users/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
            'strict_profile_uri' => true,
        ],
        [
            'key' => 'hackerrank',
            'name' => 'HackerRank',
            'profile_template' => 'https://www.hackerrank.com/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
            'not_found_markers' => [
                'Page Not Found',
            ],
        ],
        [
            'key' => 'replit',
            'name' => 'Replit',
            'profile_template' => 'https://replit.com/@%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'producthunt',
            'name' => 'Product Hunt',
            'profile_template' => 'https://www.producthunt.com/@%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'twitch',
            'name' => 'Twitch',
            'profile_template' => 'https://www.twitch.tv/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],
        [
            'key' => 'patreon',
            'name' => 'Patreon',
            'profile_template' => 'https://www.patreon.com/%s',
            'region_group' => 'americas',
            'primary_users_region' => 'americas',
        ],

        // Global
        [
            'key' => 'instagram',
            'name' => 'Instagram',
            'profile_template' => 'https://www.instagram.com/%s/',
            'region_group' => 'global',
            'primary_users_region' => 'global',
        ],
        [
            'key' => 'x',
            'name' => 'X (Twitter)',
            'profile_template' => 'https://x.com/%s',
            'region_group' => 'global',
            'primary_users_region' => 'global',
        ],
        [
            'key' => 'linkedin',
            'name' => 'LinkedIn',
            'profile_template' => 'https://www.linkedin.com/in/%s/',
            'region_group' => 'global',
            'primary_users_region' => 'global',
            'strict_profile_uri' => true,
        ],
    ],
];
