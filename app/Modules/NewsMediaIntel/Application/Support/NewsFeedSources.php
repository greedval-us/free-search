<?php

namespace App\Modules\NewsMediaIntel\Application\Support;

final class NewsFeedSources
{
    public const NEWS_API = 'newsapi';
    public const GOOGLE_NEWS = 'googlenews';
    public const BING = 'bing';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::NEWS_API,
            self::GOOGLE_NEWS,
            self::BING,
        ];
    }
}

