<?php

return [
    'validation' => ['groups_one_per_line' => 'Enter each group on a separate line.'],
    'errors' => [
        'queue_unavailable' => 'The report queue is unavailable. Contact an administrator.',
        'account_unavailable' => 'Reports require a verified, active account.',
        'access_denied' => 'Analytics is unavailable on your current plan or the daily limit has been reached.',
        'schedule_limit' => 'The limit of saved report schedules has been reached.',
        'invalid_groups' => 'Enter valid public Telegram groups.',
        'invalid_interval' => 'Choose an interval of 1, 3, 7 days or a month.',
        'invalid_time' => 'Enter a time in HH:MM format.',
        'invalid_timezone' => 'Enter a valid timezone.',
        'invalid_action' => 'Invalid schedule action.',
        'disabled' => 'The schedule has been paused or deleted.',
        'generation_failed' => 'The report could not be generated. Try running it again.',
        'attempts_exhausted' => 'The report could not be generated after several attempts.',
        'collection_limit' => 'There are too many messages for a complete report. Contact an administrator to increase the collection limit.',
        'pagination_stalled' => 'Telegram did not allow the full period to be read. Try running the report later.',
    ],
];
