<?php

use App\Integrations\TelegramBot\TrackingArtifactProvider;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserApplicationServiceInterface;
use App\Modules\Bluesky\Parser\Contracts\BlueskyParserExportBuilderInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserApplicationServiceInterface;
use App\Modules\Mastodon\Parser\Contracts\MastodonParserExportBuilderInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Parser\Contracts\TelegramParserExportBuilderInterface;
use App\Modules\TelegramBot\Application\Actions\FilesAction;
use App\Modules\TelegramBot\Application\Actions\MenuAction;
use App\Modules\TelegramBot\Application\Actions\SendFileAction;
use App\Modules\TelegramBot\Infrastructure\Artifacts\ParserArtifactProvider;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserExportBuilderInterface;

return [
    'enabled' => (bool) env('TELEGRAM_BOT_ENABLED', false),
    'bot_id' => (int) env('TELEGRAM_BOT_ID', 0),
    'username' => env('TELEGRAM_BOT_USERNAME', ''),
    'webhook_secret' => env('TELEGRAPH_WEBHOOK_SECRET', ''),
    'queue' => [
        'connection' => env('TELEGRAM_BOT_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('TELEGRAM_BOT_QUEUE', 'default'),
        'timeout' => 120,
        'retry_window_minutes' => 30,
        'max_exceptions' => 3,
        'backoff' => [5, 30, 120],
        'dispatch_retry_seconds' => 300,
        'overlap_release_seconds' => 2,
        'lock_grace_seconds' => 30,
        'max_retry_after_seconds' => 3600,
        'default_retry_after_seconds' => 30,
    ],
    'link_ttl_seconds' => (int) env('TELEGRAM_BOT_LINK_TTL_SECONDS', 600),
    'page_size' => 5,
    'max_page' => 1000,
    'max_document_bytes' => (int) env('TELEGRAM_BOT_MAX_DOCUMENT_BYTES', 49 * 1024 * 1024),
    'messages_per_second' => 10,
    'update_retention_seconds' => 7 * 86400,
    'delivery_retention_days' => 30,
    'batch_size' => 100,
    'temporary_directory' => 'telegram-bot/temporary',
    'temporary_retention_hours' => 2,
    'cleanup_time' => '04:30',
    'http_limits' => ['status' => 30, 'link' => 6, 'preferences' => 10],
    'actions' => [MenuAction::class, FilesAction::class, SendFileAction::class],
    'artifact_providers' => [ParserArtifactProvider::class, TrackingArtifactProvider::class],
    'menus' => [
        'main' => [
            ['label' => 'menu.exports', 'action' => 'files', 'parameters' => ['k' => 'parser'], 'linked' => true],
            ['label' => 'menu.tracking', 'action' => 'files', 'parameters' => ['k' => 'tracking'], 'linked' => true],
            ['label' => 'menu.settings', 'path' => '/settings/telegram'],
            ['label' => 'menu.help', 'action' => 'menu', 'parameters' => ['p' => 'help']],
            ['label' => 'menu.webapp', 'path' => '/dashboard', 'webapp' => true],
        ],
        'help' => [
            ['label' => 'menu.settings', 'path' => '/settings/telegram'],
            ['label' => 'menu.back', 'action' => 'menu'],
        ],
    ],
    'parsers' => [
        'telegram' => ['service' => TelegramParserApplicationServiceInterface::class, 'builder' => TelegramParserExportBuilderInterface::class],
        'youtube' => ['service' => YouTubeParserApplicationServiceInterface::class, 'builder' => YouTubeParserExportBuilderInterface::class],
        'mastodon' => ['service' => MastodonParserApplicationServiceInterface::class, 'builder' => MastodonParserExportBuilderInterface::class],
        'bluesky' => ['service' => BlueskyParserApplicationServiceInterface::class, 'builder' => BlueskyParserExportBuilderInterface::class],
    ],
];
