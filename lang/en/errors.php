<?php

return [
    'api' => [
        'generic' => 'Something went wrong. Please try again later.',
        'validation' => 'Please check the entered data and try again.',
        'unauthorized' => 'Please sign in and try again.',
        'forbidden' => 'You do not have access to this action.',
        'not_found' => 'The requested resource was not found.',
        'too_many_requests' => 'Too many requests. Please wait a bit and try again.',
        'service_unavailable' => 'The service is temporarily unavailable. Please try again later.',
        'telegram' => [
            'not_configured' => 'Telegram integration is temporarily unavailable.',
            'load_messages_failed' => 'Failed to load messages for the current query.',
            'author_peer_resolve_failed' => 'Failed to resolve Telegram peer for the specified author ID.',
            'parser_messages_failed' => 'Failed to load messages for parser.',
        ],
        'site_intel' => [
            'invalid_target' => 'Invalid target URL or domain.',
        ],
        'youtube' => [
            'not_configured' => 'YouTube integration is temporarily unavailable.',
            'unavailable' => 'Could not connect to YouTube right now. Please try again later.',
            'request_failed' => 'YouTube could not process the request. Please refine the query and try again.',
            'rate_limited' => 'YouTube rate limit has been reached. Please try again later.',
            'channel_not_found' => 'YouTube channel was not found. Check the channel ID or handle and try again.',
        ],
        'mastodon' => [
            'not_configured' => 'Mastodon integration is temporarily unavailable.',
            'invalid_base_url' => 'Mastodon integration is temporarily unavailable.',
            'unavailable' => 'Could not connect to Mastodon right now. Please try again later.',
            'request_failed' => 'Mastodon could not process the request. Please refine the query and try again.',
            'rate_limited' => 'Mastodon rate limit has been reached. Please try again later.',
            'account_not_found' => 'Mastodon account was not found.',
            'hashtag_not_found' => 'Mastodon hashtag was not found.',
        ],
        'bluesky' => [
            'not_configured' => 'Bluesky integration is temporarily unavailable.',
            'invalid_base_url' => 'Bluesky integration is temporarily unavailable.',
            'unavailable' => 'Could not connect to Bluesky right now. Please try again later.',
            'request_failed' => 'Bluesky could not process the request. Please refine the query and try again.',
            'rate_limited' => 'Bluesky rate limit has been reached. Please try again later.',
            'authentication_failed' => 'Bluesky authentication failed. Please try again later.',
            'account_not_found' => 'Bluesky account was not found.',
            'hashtag_not_found' => 'Bluesky hashtag was not found.',
        ],
    ],
];
