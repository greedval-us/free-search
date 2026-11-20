<?php

use App\Modules\ParserSupport\ParserRunConfig;

return [
    'retention_days' => (int) env('PARSER_RUN_RETENTION_DAYS', ParserRunConfig::DEFAULT_RETENTION_DAYS),
    'cleanup_batch_size' => max(1, (int) env('PARSER_RUN_CLEANUP_BATCH_SIZE', ParserRunConfig::DEFAULT_CLEANUP_BATCH_SIZE)),
    'cleanup_schedule' => env('PARSER_RUN_CLEANUP_SCHEDULE', ParserRunConfig::DEFAULT_CLEANUP_SCHEDULE),
    'history_limit' => max(1, (int) env('PARSER_RUN_HISTORY_LIMIT', ParserRunConfig::DEFAULT_HISTORY_LIMIT)),
    'recovery' => [
        'batch_size' => max(1, (int) env('PARSER_RUN_RECOVERY_BATCH_SIZE', ParserRunConfig::DEFAULT_RECOVERY_BATCH_SIZE)),
        'stale_after_seconds' => max(ParserRunConfig::EXECUTION_LOCK_SECONDS, (int) env('PARSER_RUN_RECOVERY_STALE_AFTER_SECONDS', ParserRunConfig::EXECUTION_LOCK_SECONDS)),
        'max_pass_seconds' => max(1, min(ParserRunConfig::MAX_RECOVERY_PASS_SECONDS, (int) env('PARSER_RUN_RECOVERY_MAX_PASS_SECONDS', ParserRunConfig::DEFAULT_RECOVERY_MAX_PASS_SECONDS))),
    ],
    'limits' => [
        'max_step_attempts' => max(1, (int) env('PARSER_RUN_MAX_STEP_ATTEMPTS', ParserRunConfig::DEFAULT_MAX_STEP_ATTEMPTS)),
        'max_records' => max(1, (int) env('PARSER_RUN_MAX_RECORDS', ParserRunConfig::DEFAULT_MAX_RECORDS)),
        'max_duration_seconds' => max(1, (int) env('PARSER_RUN_MAX_DURATION_SECONDS', ParserRunConfig::DEFAULT_MAX_DURATION_SECONDS)),
        'max_checkpoint_bytes' => max(ParserRunConfig::MIN_CHECKPOINT_BYTES, (int) env('PARSER_RUN_MAX_CHECKPOINT_BYTES', ParserRunConfig::DEFAULT_MAX_CHECKPOINT_BYTES)),
        'max_export_bytes' => max(1, (int) env('PARSER_RUN_MAX_EXPORT_BYTES', ParserRunConfig::DEFAULT_MAX_EXPORT_BYTES)),
        'max_export_cells' => max(1, (int) env('PARSER_RUN_MAX_EXPORT_CELLS', ParserRunConfig::DEFAULT_MAX_EXPORT_CELLS)),
        'max_source_requests' => max(1, (int) env('PARSER_RUN_MAX_SOURCE_REQUESTS', ParserRunConfig::DEFAULT_MAX_SOURCE_REQUESTS)),
    ],
    'queue' => [
        'enabled' => (bool) env('PARSER_RUN_QUEUE_ENABLED', true),
        'name' => env('PARSER_RUN_QUEUE_NAME', ParserRunConfig::DEFAULT_QUEUE),
        'step_delay_seconds' => max(0, (int) env('PARSER_RUN_QUEUE_STEP_DELAY_SECONDS', ParserRunConfig::DEFAULT_STEP_DELAY_SECONDS)),
    ],
];
