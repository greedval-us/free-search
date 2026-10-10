<?php

return [
    'errors' => [
        'queue_unavailable' => 'The report queue is unavailable. Contact an administrator.',
        'account_unavailable' => 'Reports require a verified, active account.',
        'access_denied' => 'This report type is unavailable on your plan or the daily limit has been reached.',
        'schedule_limit' => 'The limit of saved report schedules has been reached.',
        'invalid_targets' => 'Enter public domains or HTTP/HTTPS links, one website per line.',
        'invalid_type' => 'Choose website analytics or an SEO audit.',
        'invalid_options' => 'Check the SEO audit settings: 3 to 20 pages and a website type from the list.',
        'invalid_interval' => 'Choose an interval of 1, 3, 7 days or a month.',
        'invalid_time' => 'Choose a time in HH:MM format.',
        'invalid_timezone' => 'Choose a time zone from the list.',
        'invalid_action' => 'Invalid schedule action.',
        'invalid_target' => 'This website cannot be checked safely. Enter a public website with a public IP address.',
        'disabled' => 'The schedule has been paused or deleted.',
        'generation_failed' => 'The report could not be generated. Try running it again.',
        'attempts_exhausted' => 'The report could not be generated after several attempts.',
    ],
    'report' => [
        'title' => 'Scheduled report',
        'type' => 'Report type',
        'analytics' => 'Website analytics',
        'seo-audit' => 'SEO audit',
        'scheduled_for' => 'Scheduled check',
        'checked_at' => 'Checked at',
        'timezone' => 'Time zone',
        'methodology' => 'This report captures the website at the time of the check. The schedule frequency controls repeated checks; it does not represent traffic or changes over previous days.',
    ],
];
