<?php

return [
    'errors' => [
        'queue_unavailable' => 'The report queue is unavailable. Contact the administrator.',
        'account_unavailable' => 'Reports require a verified, active account.',
        'schedule_limit' => 'The saved schedule limit has been reached.',
        'invalid_queries' => 'Enter 1 to 3 different search queries without engine selection commands.',
        'invalid_options' => 'Check the brand, competitors, domain and search filters.',
        'invalid_interval' => 'Choose an interval of 1, 3, 7 days or a month.',
        'invalid_time' => 'Choose a time in HH:MM format.',
        'invalid_timezone' => 'Choose a time zone from the list.',
        'invalid_action' => 'Unknown schedule action.',
        'disabled' => 'The schedule is paused or deleted.',
        'generation_failed' => 'The report could not be generated. Try running it again.',
        'attempts_exhausted' => 'The report could not be generated after several attempts.',
    ],
    'report' => [
        'title' => 'Scheduled report',
        'scheduled_for' => 'Scheduled check',
        'checked_at' => 'Checked',
        'timezone' => 'Time zone',
        'frequency' => 'Delivery frequency',
        'methodology' => 'This report saves the search sample available at check time. Delivery frequency and the search time filter are separate settings. The sample is not a complete publication list or statistics for the exact interval between deliveries.',
    ],
];
