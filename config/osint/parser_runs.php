<?php

return [
    'retention_days' => (int) env('PARSER_RUN_RETENTION_DAYS', 7),
    'cleanup_batch_size' => max(1, (int) env('PARSER_RUN_CLEANUP_BATCH_SIZE', 500)),
    'cleanup_schedule' => env('PARSER_RUN_CLEANUP_SCHEDULE', '03:30'),
    'history_limit' => max(1, (int) env('PARSER_RUN_HISTORY_LIMIT', 20)),
    'recovery' => [
        'batch_size' => max(1, (int) env('PARSER_RUN_RECOVERY_BATCH_SIZE', 100)),
        'stale_after_seconds' => max(150, (int) env('PARSER_RUN_RECOVERY_STALE_AFTER_SECONDS', 150)),
    ],
    'limits' => [
        'max_step_attempts' => max(1, (int) env('PARSER_RUN_MAX_STEP_ATTEMPTS', 10000)),
        'max_records' => max(1, (int) env('PARSER_RUN_MAX_RECORDS', 100000)),
        'max_duration_seconds' => max(1, (int) env('PARSER_RUN_MAX_DURATION_SECONDS', 86400)),
        'max_checkpoint_bytes' => max(4096, (int) env('PARSER_RUN_MAX_CHECKPOINT_BYTES', 33554432)),
        'max_export_bytes' => max(1, (int) env('PARSER_RUN_MAX_EXPORT_BYTES', 67108864)),
        'max_export_cells' => max(1, (int) env('PARSER_RUN_MAX_EXPORT_CELLS', 1000000)),
        'max_source_requests' => max(1, (int) env('PARSER_RUN_MAX_SOURCE_REQUESTS', 100000)),
    ],
    'queue' => [
        'enabled' => (bool) env('PARSER_RUN_QUEUE_ENABLED', true),
        'name' => env('PARSER_RUN_QUEUE_NAME', 'default'),
        'step_delay_seconds' => max(0, (int) env('PARSER_RUN_QUEUE_STEP_DELAY_SECONDS', 2)),
    ],
];
