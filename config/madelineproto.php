<?php

return [
    'api_id' => env('TELEGRAM_API_ID'),
    'api_hash' => env('TELEGRAM_API_HASH'),
    'session_path' => env('MADELINEPROTO_SESSION_PATH', 'app/private/session/'),
    'log_path' => env('MADELINEPROTO_LOG_PATH', 'logs/madeline.log'),
];
