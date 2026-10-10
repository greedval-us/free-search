<?php

return [
    'errors' => [
        'queue_unavailable' => 'The report queue is unavailable. Contact an administrator.',
        'account_unavailable' => 'Reports require a verified, active account.',
        'access_denied' => 'Analytics is unavailable on your current plan or the daily limit has been reached.',
        'schedule_limit' => 'The limit of saved report schedules has been reached.',
        'invalid_accounts' => 'Enter a Mastodon account: name@server, local name or https://server/@name link.',
        'invalid_interval' => 'Choose an interval of 1, 3, 7 days or a month.',
        'invalid_time' => 'Choose a time in HH:MM format.',
        'invalid_timezone' => 'Choose a time zone from the list.',
        'invalid_action' => 'Invalid schedule action.',
        'disabled' => 'The schedule has been paused or deleted.',
        'generation_failed' => 'The report could not be generated. Try running it again.',
        'attempts_exhausted' => 'The report could not be generated after several attempts.',
        'collection_limit' => 'There are too many posts for a complete report. Contact an administrator to increase the collection limit.',
        'pagination_stalled' => 'Mastodon did not allow the full period to be read. Try running the report later.',
    ],
    'report' => [
        'period' => 'Publication period',
        'timezone' => 'Time zone',
        'generated_at' => 'Generated at',
        'methodology' => 'This report covers public and unlisted original posts available through the configured server during the period, excluding boosts. Favourites, boosts and replies are cumulative totals observed by that server at collection time.',
        'missing_metrics' => 'Mastodon did not return some reaction counts. Unavailable metrics are marked as No data instead of being replaced with zero.',
    ],
];
