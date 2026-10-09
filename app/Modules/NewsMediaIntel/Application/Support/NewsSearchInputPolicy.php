<?php

namespace App\Modules\NewsMediaIntel\Application\Support;

final class NewsSearchInputPolicy
{
    public const MIN_TEXT_LENGTH = 2;

    public const MAX_QUERY_LENGTH = 180;

    public const MAX_ENTITY_LENGTH = 80;

    public const MAX_DOMAIN_LENGTH = 253;

    public const MAX_COMPETITORS = 3;

    public const MAX_ENGINES = 8;

    public const MAX_PAGES = 10;

    public const CATEGORIES = ['news', 'general'];

    public const TIME_RANGES = ['', 'day', 'week', 'month', 'year'];

    public const SAFE_SEARCH_OFF = 0;

    public const SAFE_SEARCH_MODERATE = 1;

    public const SAFE_SEARCH_STRICT = 2;

    public const SAFE_SEARCH_LEVELS = [self::SAFE_SEARCH_OFF, self::SAFE_SEARCH_MODERATE, self::SAFE_SEARCH_STRICT];

    public static function hasForbiddenQuerySyntax(string $query): bool
    {
        return preg_match('/(?:^|\s)[!:][^\s]+|[\p{Cc}]/u', $query) !== 0;
    }
}
