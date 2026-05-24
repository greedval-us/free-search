<?php

return [
    'title' => 'Панель контроля',

    'sections' => [
        'overview' => 'Обзор',
        'visual_analytics' => 'Визуальная аналитика',
        'most_used_modules' => 'Самые используемые модули (:days дн.)',
        'daily_activity' => 'Дневная активность (:days дн.)',
    ],

    'metrics' => [
        'registered_users' => 'Зарегистрированные пользователи',
        'new_users_24h' => 'Новые за 24ч',
        'new_users_7d' => 'Новые за 7д',
        'premium_users_active' => 'Премиум (активные)',
        'blocked_users' => 'Заблокированные',
        'requests_24h' => 'Запросы за 24ч',
        'requests_7d' => 'Запросы за 7д',
        'used_modules_30d' => 'Модули за 30д',
        'errors_5xx_24h' => 'Ошибки 5xx за 24ч',
        'avg_response_24h_ms' => 'Ср. ответ 24ч (мс)',
        'queue_ready_now' => 'Очередь: готово сейчас',
        'failed_jobs_24h' => 'Проваленные задачи за 24ч',
    ],

    'table' => [
        'module' => 'Модуль',
        'unknown_module' => 'Неизвестно',
        'requests' => 'Запросы',
        'unique_users' => 'Уникальные пользователи',
        'errors_4xx' => 'Ошибки 4xx',
        'errors_5xx' => 'Ошибки 5xx',
        'date' => 'Дата',
        'registrations' => 'Регистрации',
        'active_users' => 'Активные пользователи',
    ],

    'visual' => [
        'control_focus' => 'Аналитика панели контроля',
        'control_subtitle' => 'Рост пользователей, нагрузка, очередь и ошибки в одном месте',
        'period_days' => ':days дн.',
        'premium_share' => 'Доля премиум',
        'blocked_share' => 'Доля блокировок',
        'error_share_24h' => 'Доля ошибок (24ч)',
        'requests_growth_signal' => 'Сигнал роста запросов',
        'signal_up' => 'Рост',
        'signal_stable' => 'Стабильно',
        'queue_status' => 'Состояние очереди',
        'ready_now' => 'Готово сейчас',
        'in_progress' => 'В работе',
        'failed_jobs' => 'Проваленные задачи',
        'last_24h' => 'За последние 24 часа',
        'requests_by_day' => 'Запросы по дням (:days)',
        'daily_trend' => 'Тренд запросов и активных пользователей',
        'top_modules' => 'Топ модулей (:days)',
        'by_total_requests' => 'По общему числу запросов',
        'max' => 'Макс',
        'no_module_usage' => 'Использование модулей пока отсутствует.',
    ],
];
