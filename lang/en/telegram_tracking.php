<?php

return [
    'errors' => [
        'limit' => 'Your active tracking limit has been reached. Pause or stop another task first.',
        'open_limit' => 'Too many unfinished tasks. Stop an unused task first.',
        'group_unavailable' => 'A group does not exist or its history is unavailable without joining. No task was created.',
        'duplicate_group' => 'These entries refer to the same group. Remove the duplicate.',
        'session_unavailable' => 'No authorized Telegram session is available. Please contact support.',
        'queue_unavailable' => 'The tracking queue is not configured. Please contact support.',
        'busy' => 'Telegram is busy. Please try again shortly.',
        'cooldown' => 'Telegram requests are temporarily delayed. Please try again later.',
        'flood_wait' => 'Telegram requires a cooldown. Collection will continue after it ends.',
        'collection_failed' => 'Could not read Telegram history. Please try again later.',
        'pagination_stalled' => 'Telegram returned a repeated page. Collection will be retried without skipping data.',
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
