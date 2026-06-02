<?php

declare(strict_types=1);

return [
    'navigation' => [
        'operations' => 'Operations',
        'security' => 'Security',
    ],

    'resources' => [
        'registered_users' => 'Registered Users',
        'user_activity' => 'User Activity',
        'queue_jobs' => 'Queue Jobs',
        'failed_jobs' => 'Failed Jobs',
        'admin_audit_log' => 'Admin Audit Log',
        'user_subscriptions' => 'User subscriptions',
        'feature_usage_daily' => 'Daily feature usage',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'E-mail',
        'account_type' => 'Account type',
        'plan' => 'Plan',
        'subscription_ends_at' => 'Subscription ends at',
        'quota_remaining' => 'Quota left today',
        'starts_at' => 'Starts at',
        'ends_at' => 'Ends at',
        'metadata' => 'Metadata',
        'usage_date' => 'Usage date',
        'feature' => 'Feature',
        'used' => 'Used',
        'updated_at' => 'Updated At',
        'blocked' => 'Blocked',
        'telegram_id' => 'Telegram ID',
        'requests' => 'Requests',
        'created_at' => 'Created At',
        'date' => 'Date',
        'user' => 'User',
        'method' => 'Method',
        'status' => 'Status',
        'response_ms' => 'Response ms',
        'module' => 'Module',
        'action' => 'Action',
        'query' => 'Query',
        'path' => 'Path',
        'route' => 'Route',
        'queue' => 'Queue',
        'attempts' => 'Attempts',
        'available_at' => 'Available At',
        'reserved_at' => 'Reserved At',
        'job' => 'Job',
        'failed_at' => 'Failed At',
        'connection' => 'Connection',
        'error' => 'Error',
        'uuid' => 'UUID',
        'admin' => 'Admin',
        'target' => 'Target',
        'target_id' => 'Target ID',
        'changed_fields' => 'Changed fields',
        'changed_keys' => 'Changed keys',
        'change_details' => 'Change details',
    ],

    'tags' => [
        'all' => 'All',
        'errors_4xx' => 'Errors 4xx',
        'errors_5xx' => 'Errors 5xx',
        'slow_1500' => 'Slow > 1500ms',
        'today' => 'Today',
        'retrying' => 'Retrying',
        'ready_now' => 'Ready now',
        'last_24h' => 'Last 24h',
        'blocked_users' => 'Blocked users',
        'paid_users' => 'Paid users',
        'plus_users' => 'Plus users',
        'pro_users' => 'Pro users',
        'active_subscriptions' => 'Active subscriptions',
    ],

    'values' => [
        'yes' => 'yes',
        'no' => 'no',
        'blocked' => 'blocked',
        'active' => 'active',
        'canceled' => 'canceled',
        'expired' => 'expired',
        'user' => 'user',
        'admin' => 'admin',
        'moderator' => 'moderator',
        'not_available' => '-',
        'null' => 'null',
        'true' => 'true',
        'false' => 'false',
        'complex' => '[complex]',
        'unknown' => 'unknown',
    ],

    'quota_modules' => [
        'mastodon' => 'Mastodon',
        'site-intel' => 'Site Intel',
        'telegram' => 'Telegram',
        'youtube' => 'YouTube',
    ],

    'quota_capabilities' => [
        'analytics' => 'analytics',
        'seo-audit' => 'SEO audit',
        'parser' => 'parser',
    ],

    'quota_capability_short' => [
        'analytics' => 'A',
        'seo-audit' => 'SEO',
        'parser' => 'P',
    ],

    'quota_resources' => [
        'mastodon' => [
            'analytics' => 'Mastodon analytics',
            'parser' => 'Mastodon parser',
        ],
        'site-intel' => [
            'analytics' => 'Site Intel analytics',
            'seo-audit' => 'SEO audit',
        ],
        'telegram' => [
            'analytics' => 'Telegram analytics',
            'parser' => 'Telegram parser',
        ],
        'youtube' => [
            'analytics' => 'YouTube analytics',
            'parser' => 'YouTube parser',
        ],
    ],
];
