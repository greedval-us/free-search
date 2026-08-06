<?php

return [
    'retention_days' => (int) env('PARSER_RUN_RETENTION_DAYS', 7),
    'cleanup_batch_size' => max(1, (int) env('PARSER_RUN_CLEANUP_BATCH_SIZE', 500)),
    'cleanup_schedule' => env('PARSER_RUN_CLEANUP_SCHEDULE', '03:30'),
    'history_limit' => max(1, (int) env('PARSER_RUN_HISTORY_LIMIT', 20)),
];
