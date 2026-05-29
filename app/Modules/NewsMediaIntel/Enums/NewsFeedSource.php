<?php

namespace App\Modules\NewsMediaIntel\Enums;

enum NewsFeedSource: string
{
    case NewsApi = 'newsapi';
    case GoogleNews = 'googlenews';
    case Bing = 'bing';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

