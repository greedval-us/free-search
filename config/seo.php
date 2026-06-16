<?php

return [
    'sitemap' => [
        'pages' => [
            [
                'route' => 'home',
                'priority' => '1.0',
                'changefreq' => 'weekly',
            ],
            [
                'route' => 'privacy',
                'priority' => '0.2',
                'changefreq' => 'yearly',
            ],
            [
                'route' => 'terms',
                'priority' => '0.2',
                'changefreq' => 'yearly',
            ],
        ],
    ],
    'pages' => [
        'welcome' => [
            'path' => '/',
            'title' => [
                'en' => 'Uraboros | Intelligence Workspace',
                'ru' => 'Uraboros | Intelligence Workspace',
            ],
            'description' => [
                'en' => 'Uraboros combines OSINT search, analytics, source validation, SEO audit, and technical website intelligence in one workspace.',
                'ru' => 'Uraboros объединяет OSINT-поиск, аналитику, проверку источников, SEO-аудит и техническую разведку сайта в одном рабочем пространстве.',
            ],
        ],
        'privacy' => [
            'path' => '/privacy',
            'title' => [
                'en' => 'Privacy Policy',
                'ru' => 'Политика конфиденциальности',
            ],
            'description' => [
                'en' => 'Uraboros privacy policy explaining what data is processed, why it is used, and how it is protected.',
                'ru' => 'Политика конфиденциальности Uraboros: какие данные обрабатываются, зачем они нужны и как защищаются.',
            ],
        ],
        'terms' => [
            'path' => '/terms',
            'title' => [
                'en' => 'Terms of Service',
                'ru' => 'Пользовательское соглашение',
            ],
            'description' => [
                'en' => 'Uraboros terms of service covering access conditions, acceptable use, and service limitations.',
                'ru' => 'Пользовательское соглашение Uraboros с условиями доступа, правилами использования и ограничениями сервиса.',
            ],
        ],
    ],
];
