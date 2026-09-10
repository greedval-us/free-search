<?php

return [
    'validation' => [
        'groups_one_per_line' => 'Enter each group or channel on a new line, not separated by spaces.',
    ],
    'errors' => [
        'limit' => 'Your active tracking limit has been reached. Pause or stop another task first.',
        'open_limit' => 'Too many unfinished tasks. Stop an unused task first.',
        'group_unavailable' => 'A group or channel was not found or is inaccessible to the service. No task was created.',
        'duplicate_group' => 'These entries refer to the same group. Remove the duplicate.',
        'session_unavailable' => 'The Telegram connection is unavailable. Please contact support.',
        'queue_unavailable' => 'Tracking is temporarily unavailable. Please contact support.',
        'busy' => 'Telegram is busy. Please try again shortly.',
        'cooldown' => 'Telegram requests are temporarily delayed. Please try again later.',
        'flood_wait' => 'Telegram requires a cooldown. Collection will continue after it ends.',
        'collection_failed' => 'Could not load messages. Please try again later.',
        'search_incomplete' => 'Search did not finish. We will retry automatically.',
        'pagination_stalled' => 'Collection was interrupted. We will retry automatically.',
        'account_unavailable' => 'This account cannot start tracking.',
        'finished' => 'This tracking has ended. Create a new task.',
        'renew_too_early' => 'Renewal is available during the last seven days before expiry.',
        'invalid_action' => 'Unsupported tracking action.',
    ],
    'export' => [
        'messages' => 'Messages', 'summary' => 'Summary', 'field' => 'Field', 'value' => 'Value',
        'name' => 'Tracking', 'query' => 'Match criterion', 'started' => 'Started at', 'expires' => 'Expires at', 'generated' => 'Generated at',
        'columns' => ['Group', 'Group ID', 'Message ID', 'Sender ID', 'Message', 'Sent at (UTC)', 'Received at (UTC)', 'Telegram URL'],
    ],
];
