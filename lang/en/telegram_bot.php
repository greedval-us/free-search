<?php

return [
    'menu' => [
        'tracking' => 'Tracking reports',
        'analytics_reports' => 'Telegram analytics reports',
        'youtube_analytics_reports' => 'YouTube analytics reports',
        'bluesky_analytics_reports' => 'Bluesky analytics reports',
        'mastodon_analytics_reports' => 'Mastodon analytics reports',
        'site_intel_reports' => 'Website reports',
        'news_media_reports' => 'News and media reports',
        'exports' => 'My exports', 'settings' => 'Link account / Settings',
        'help' => 'Help', 'webapp' => 'Open Uraboros', 'back' => 'Main menu', 'previous' => 'Previous', 'next' => 'Next',
    ],
    'screens' => [
        'main' => 'Uraboros. Select an action below.',
        'unlinked' => 'Link your verified website account in Telegram settings to receive notifications and parser exports. Only private chats are supported.',
        'help' => 'Generate parser exports and schedule Telegram, YouTube, Bluesky and Mastodon analytics reports, website analytics, SEO audits and news and media analytics on the website, then download them here. To receive reports automatically, turn on bot delivery in the schedule and automatic file delivery in settings. Announcements have a separate setting. Files already sent to Telegram cannot be recalled by website cleanup. The WebApp uses your normal website sign-in.',
        'confirm_on_site' => 'Telegram confirmed. Return to the website, check the displayed Telegram ID and confirm the link. No account data is available until that confirmation.',
        'invalid_link' => 'This link is invalid, expired or already claimed. Create a new link from your website account.',
        'files' => 'Choose a file. Only your currently available results are listed.',
        'empty' => 'No available files. Generate a result on the website first; expired files are not available here.',
        'queued' => 'Delivery queued. Access and file availability will be checked again before sending.',
    ],
    'errors' => [
        'already_linked' => 'This website or Telegram account is already linked. Disconnect it first.',
        'link_expired' => 'Link request expired or has not been confirmed in Telegram. Start again.',
        'file_unavailable' => 'This file is unavailable or expired. Open the website to generate a new result.',
        'file_too_large' => 'This file exceeds the Telegram delivery limit. Download it from your account on the website.',
    ],
];
