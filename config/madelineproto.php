<?php

return [
    'api_id' => env('TELEGRAM_API_ID'),
    'api_hash' => env('TELEGRAM_API_HASH'),
    'session_path' => env('MADELINEPROTO_SESSION_PATH', 'app/private/session/'),
    'log_path' => env('MADELINEPROTO_LOG_PATH', 'logs/madeline.log'),
    'admin_auth' => [
        'ttl_minutes' => 15,
        'max_sessions' => 20,
        'max_name_length' => 24,
        'requests_per_minute' => 6,
        'rpc_timeout_seconds' => 30,
        'connection_timeout_seconds' => 10,
        'flood_timeout_seconds' => 5,
        'page_size' => 15,
    ],
];
