<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'default_period' => 7,
        'periods' => [7, 30, 90],
        'top_modules_limit' => 6,
        'slow_response_ms' => 1500,
        'queue_backlog_warning' => 25,
        'error_rate_warning_percent' => 2.0,
    ],
];
