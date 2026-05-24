<?php

return [
    'title' => 'Control Dashboard',

    'sections' => [
        'overview' => 'Overview',
        'visual_analytics' => 'Visual analytics',
        'most_used_modules' => 'Most used modules (:days days)',
        'daily_activity' => 'Daily activity (:days days)',
    ],

    'metrics' => [
        'registered_users' => 'Registered users',
        'new_users_24h' => 'New users 24h',
        'new_users_7d' => 'New users 7d',
        'premium_users_active' => 'Premium users (active)',
        'blocked_users' => 'Blocked users',
        'requests_24h' => 'Requests in 24h',
        'requests_7d' => 'Requests in 7d',
        'used_modules_30d' => 'Used modules 30d',
        'errors_5xx_24h' => 'Errors 5xx in 24h',
        'avg_response_24h_ms' => 'Avg response 24h (ms)',
        'queue_ready_now' => 'Queue ready now',
        'failed_jobs_24h' => 'Failed jobs in 24h',
    ],

    'table' => [
        'module' => 'Module',
        'unknown_module' => 'Unknown',
        'requests' => 'Requests',
        'unique_users' => 'Unique users',
        'errors_4xx' => 'Errors 4xx',
        'errors_5xx' => 'Errors 5xx',
        'date' => 'Date',
        'registrations' => 'Registrations',
        'active_users' => 'Active users',
    ],

    'visual' => [
        'control_focus' => 'Control panel analytics',
        'control_subtitle' => 'User growth, system load, queue and failure state in one place',
        'period_days' => ':days days',
        'premium_share' => 'Premium share',
        'blocked_share' => 'Blocked share',
        'error_share_24h' => 'Error share (24h)',
        'requests_growth_signal' => 'Requests growth signal',
        'signal_up' => 'Up',
        'signal_stable' => 'Stable',
        'queue_status' => 'Queue status',
        'ready_now' => 'Ready now',
        'in_progress' => 'In progress',
        'failed_jobs' => 'Failed jobs',
        'last_24h' => 'Last 24 hours',
        'requests_by_day' => 'Requests by day (:days)',
        'daily_trend' => 'Requests and active users trend',
        'top_modules' => 'Top modules (:days)',
        'by_total_requests' => 'By total requests',
        'max' => 'Max',
        'no_module_usage' => 'No module usage yet.',
    ],
];
