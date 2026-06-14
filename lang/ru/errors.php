<?php

return [
    'api' => [
        'generic' => 'Что-то пошло не так. Попробуйте ещё раз позже.',
        'validation' => 'Проверьте введённые данные и попробуйте снова.',
        'unauthorized' => 'Войдите в систему и повторите попытку.',
        'forbidden' => 'У вас нет доступа к этому действию.',
        'not_found' => 'Запрошенный ресурс не найден.',
        'too_many_requests' => 'Слишком много запросов. Подождите немного и попробуйте снова.',
        'service_unavailable' => 'Сервис временно недоступен. Попробуйте ещё раз позже.',
        'telegram' => [
            'not_configured' => 'Интеграция с Telegram временно недоступна.',
            'load_messages_failed' => 'Не удалось загрузить сообщения для текущего запроса.',
            'author_peer_resolve_failed' => 'Не удалось определить Telegram peer для указанного ID автора.',
            'parser_messages_failed' => 'Не удалось загрузить сообщения для парсера.',
        ],
        'site_intel' => [
            'invalid_target' => 'Некорректный URL или домен цели.',
        ],
        'youtube' => [
            'not_configured' => 'Интеграция с YouTube временно недоступна.',
            'unavailable' => 'Сейчас не удалось связаться с YouTube. Попробуйте позже.',
            'request_failed' => 'YouTube не смог обработать запрос. Уточните параметры и попробуйте снова.',
            'rate_limited' => 'Лимит запросов YouTube исчерпан. Попробуйте позже.',
            'channel_not_found' => 'Канал YouTube не найден. Проверьте ID канала или handle.',
        ],
        'mastodon' => [
            'not_configured' => 'Интеграция с Mastodon временно недоступна.',
            'invalid_base_url' => 'Интеграция с Mastodon временно недоступна.',
            'unavailable' => 'Сейчас не удалось связаться с Mastodon. Попробуйте позже.',
            'request_failed' => 'Mastodon не смог обработать запрос. Уточните параметры и попробуйте снова.',
            'rate_limited' => 'Лимит запросов Mastodon исчерпан. Попробуйте позже.',
            'account_not_found' => 'Аккаунт Mastodon не найден.',
            'hashtag_not_found' => 'Хэштег Mastodon не найден.',
        ],
        'bluesky' => [
            'not_configured' => 'Интеграция с Bluesky временно недоступна.',
            'invalid_base_url' => 'Интеграция с Bluesky временно недоступна.',
            'unavailable' => 'Сейчас не удалось связаться с Bluesky. Попробуйте позже.',
            'request_failed' => 'Bluesky не смог обработать запрос. Уточните параметры и попробуйте снова.',
            'rate_limited' => 'Лимит запросов Bluesky исчерпан. Попробуйте позже.',
            'authentication_failed' => 'Ошибка авторизации Bluesky. Попробуйте позже.',
            'account_not_found' => 'Аккаунт Bluesky не найден.',
            'hashtag_not_found' => 'Хэштег Bluesky не найден.',
        ],
    ],
];
