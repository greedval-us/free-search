<?php

return [
    'retention_days' => (int) env('PARSER_RUN_RETENTION_DAYS', 7),
    'cleanup_batch_size' => max(1, (int) env('PARSER_RUN_CLEANUP_BATCH_SIZE', 500)),
    'cleanup_schedule' => env('PARSER_RUN_CLEANUP_SCHEDULE', '03:30'),
    'history_limit' => max(1, (int) env('PARSER_RUN_HISTORY_LIMIT', 20)),
    'queue' => [
        'enabled' => (bool) env('PARSER_RUN_QUEUE_ENABLED', true),
        'name' => env('PARSER_RUN_QUEUE_NAME', 'default'),
        'step_delay_seconds' => max(0, (int) env('PARSER_RUN_QUEUE_STEP_DELAY_SECONDS', 2)),
    ],
];
