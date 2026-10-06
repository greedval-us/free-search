<?php

namespace App\Services\Monitoring;

use App\Modules\Bluesky\Monitoring\BlueskySourceAdapter;
use App\Modules\Mastodon\Monitoring\MastodonSourceAdapter;
use App\Modules\NewsMediaIntel\Monitoring\NewsSourceAdapter;
use App\Modules\Telegram\Monitoring\TelegramSourceAdapter;
use App\Modules\YouTube\Monitoring\YouTubeSourceAdapter;
use App\Services\Monitoring\Contracts\SourceAdapter;

final readonly class SourceAdapterRegistry
{
    private array $adapters;

    public function __construct(TelegramSourceAdapter $telegram, YouTubeSourceAdapter $youtube,
        BlueskySourceAdapter $bluesky, MastodonSourceAdapter $mastodon, NewsSourceAdapter $news)
    {
        $this->adapters = compact('telegram', 'youtube', 'bluesky', 'mastodon', 'news');
    }

    public function for(string $platform): SourceAdapter
    {
        return $this->adapters[$platform] ?? throw new SourceUnavailable('unsupported_platform');
    }

    public function resolve(string $platform, string $input): array
    {
        return $this->for($platform)->resolve($input);
    }
}
