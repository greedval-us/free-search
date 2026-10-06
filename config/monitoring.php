<?php

$developmentLimits = [
    'free' => ['active_projects' => 1, 'saved_projects' => 5, 'sources' => 3, 'min_interval_minutes' => 360, 'reports_daily' => 2, 'items_daily' => 1000, 'retention_days' => 90],
    'plus' => ['active_projects' => 3, 'saved_projects' => 10, 'sources' => 10, 'min_interval_minutes' => 180, 'reports_daily' => 10, 'items_daily' => 10000, 'retention_days' => 180],
    'pro' => ['active_projects' => 5, 'saved_projects' => 20, 'sources' => 20, 'min_interval_minutes' => 60, 'reports_daily' => 30, 'items_daily' => 30000, 'retention_days' => 365],
];
foreach ($developmentLimits as $plan => &$limits) {
    foreach ($limits as $key => &$limit) {
        $envKey = $key === 'reports_daily' ? 'ACCESS_'.strtoupper($plan).'_MONITORING_REPORT_DAILY_LIMIT'
            : 'MONITORING_'.strtoupper($plan).'_'.strtoupper($key);
        $limit = max(0, (int) env($envKey, $limit));
    }
    unset($limit);
}
unset($limits);

return [
    'queue' => env('MONITORING_QUEUE', 'monitoring'),
    'connection' => env('MONITORING_QUEUE_CONNECTION', 'database'),
    'timezone' => env('MONITORING_TIMEZONE', 'Europe/Moscow'),
    'lease_seconds' => 180,
    'api_lease_seconds' => 180,
    'request_gap_seconds' => 1,
    'recovery_seconds' => 240,
    'step_delay_seconds' => 2,
    'max_attempts' => 6,
    'max_pages' => 20,
    'page_size' => 100,
    'max_items_per_window' => 2000,
    'max_text_bytes' => 16000,
    'max_report_items' => 20000,
    'max_topic_words' => 128,
    'max_topic_terms' => 2000,
    'initial_lookback_days' => min(31, max(0, (int) env('MONITORING_INITIAL_LOOKBACK_DAYS', 7))),
    'window_hours' => 24,
    'report_wait_seconds' => max(0, (int) env('MONITORING_REPORT_WAIT_SECONDS', 300)),
    'catch_up_reports' => min(1, max(0, (int) env('MONITORING_CATCH_UP_REPORTS', 1))),
    'tick_limit' => 100,
    'plans' => $developmentLimits,
];
