<?php

declare(strict_types=1);

return [
    'navigation' => [
        'operations' => 'Операции',
        'security' => 'Безопасность',
    ],

    'resources' => [
        'registered_users' => 'Пользователи',
        'user_activity' => 'Активность пользователей',
        'queue_jobs' => 'Задачи очереди',
        'failed_jobs' => 'Проваленные задачи',
        'admin_audit_log' => 'Журнал аудита админки',
        'user_subscriptions' => 'Подписки пользователей',
        'feature_usage_daily' => 'Дневное использование квот',
    ],

    'fields' => [
        'name' => 'Имя',
        'email' => 'E-mail',
        'account_type' => 'Тип аккаунта',
        'plan' => 'Тариф',
        'subscription_ends_at' => 'Подписка действует до',
        'quota_remaining' => 'Остаток квот сегодня',
        'starts_at' => 'Начало',
        'ends_at' => 'Окончание',
        'metadata' => 'Метаданные',
        'usage_date' => 'Дата использования',
        'feature' => 'Функция',
        'used' => 'Использовано',
        'updated_at' => 'Обновлено',
        'blocked' => 'Блокировка',
        'telegram_id' => 'Telegram ID',
        'requests' => 'Запросы',
        'created_at' => 'Дата создания',
        'date' => 'Дата',
        'user' => 'Пользователь',
        'method' => 'Метод',
        'status' => 'Статус',
        'response_ms' => 'Ответ (мс)',
        'module' => 'Модуль',
        'action' => 'Действие',
        'query' => 'Запрос',
        'path' => 'Путь',
        'route' => 'Маршрут',
        'queue' => 'Очередь',
        'attempts' => 'Попытки',
        'available_at' => 'Доступно с',
        'reserved_at' => 'Зарезервировано в',
        'job' => 'Задача',
        'failed_at' => 'Ошибка в',
        'connection' => 'Подключение',
        'error' => 'Ошибка',
        'uuid' => 'UUID',
        'admin' => 'Админ',
        'target' => 'Цель',
        'target_id' => 'ID цели',
        'changed_fields' => 'Измененных полей',
        'changed_keys' => 'Измененные ключи',
        'change_details' => 'Детали изменений',
    ],

    'tags' => [
        'all' => 'Все',
        'errors_4xx' => 'Ошибки 4xx',
        'errors_5xx' => 'Ошибки 5xx',
        'slow_1500' => 'Медленные > 1500мс',
        'today' => 'Сегодня',
        'retrying' => 'Повторные попытки',
        'ready_now' => 'Готово сейчас',
        'last_24h' => 'Последние 24ч',
        'blocked_users' => 'Заблокированные',
        'paid_users' => 'Платные пользователи',
        'plus_users' => 'Plus пользователи',
        'pro_users' => 'Pro пользователи',
        'active_subscriptions' => 'Активные подписки',
    ],

    'values' => [
        'yes' => 'да',
        'no' => 'нет',
        'blocked' => 'заблокирован',
        'active' => 'активен',
        'canceled' => 'отменена',
        'expired' => 'истекла',
        'user' => 'пользователь',
        'admin' => 'админ',
        'moderator' => 'модератор',
        'not_available' => '-',
        'null' => 'null',
        'true' => 'true',
        'false' => 'false',
        'complex' => '[сложный объект]',
        'unknown' => 'неизвестно',
    ],

    'quota_modules' => [
        'mastodon' => 'Mastodon',
        'site-intel' => 'Site Intel',
        'telegram' => 'Telegram',
        'youtube' => 'YouTube',
    ],

    'quota_capabilities' => [
        'analytics' => 'аналитика',
        'seo-audit' => 'SEO аудит',
        'parser' => 'парсер',
    ],

    'quota_capability_short' => [
        'analytics' => 'А',
        'seo-audit' => 'SEO',
        'parser' => 'П',
    ],

    'quota_resources' => [
        'mastodon' => [
            'analytics' => 'Mastodon аналитика',
        ],
        'site-intel' => [
            'analytics' => 'Site Intel аналитика',
            'seo-audit' => 'SEO аудит',
        ],
        'telegram' => [
            'analytics' => 'Telegram аналитика',
            'parser' => 'Telegram парсер',
        ],
        'youtube' => [
            'analytics' => 'YouTube аналитика',
            'parser' => 'YouTube парсер',
        ],
    ],
];
