<?php

return [
    'errors' => [
        'queue_unavailable' => 'The report queue is unavailable. Contact an administrator.',
        'account_unavailable' => 'Reports require a verified, active account.',
        'access_denied' => 'Analytics is unavailable on your current plan or the daily limit has been reached.',
        'schedule_limit' => 'The limit of saved report schedules has been reached.',
        'invalid_channels' => 'Enter a public channel: @handle, channel ID or youtube.com/@handle link.',
        'invalid_interval' => 'Choose an interval of 1, 3, 7 days or a month.',
        'invalid_time' => 'Choose a time in HH:MM format.',
        'invalid_timezone' => 'Choose a time zone from the list.',
        'invalid_action' => 'Invalid schedule action.',
        'disabled' => 'The schedule has been paused or deleted.',
        'generation_failed' => 'The report could not be generated. Try running it again.',
        'attempts_exhausted' => 'The report could not be generated after several attempts.',
        'collection_limit' => 'There are too many videos for a complete report. Contact an administrator to increase the collection limit.',
        'pagination_stalled' => 'YouTube did not allow the full period to be read. Try running the report later.',
    ],
    'report' => [
        'period' => 'Video publication period',
        'timezone' => 'Time zone',
        'generated_at' => 'Generated at',
        'methodology' => 'This report covers videos published during the selected period. Views, likes and comments are the cumulative totals for those videos at the time the report was generated.',
        'missing_metrics' => 'YouTube did not return some like or comment counts. Unavailable metrics are marked as No data instead of being replaced with zero.',
    ],
];
