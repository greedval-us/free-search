<?php

namespace App\Modules\SiteIntel\Support;

use App\Support\Domains\DomainNormalizer as SharedDomainNormalizer;

final class DomainNormalizer
{
    public static function normalizeDomain(string $value): ?string
    {
        return SharedDomainNormalizer::normalizeDomain($value);
    }

    public static function normalizeUrl(string $value): ?string
    {
        return SharedDomainNormalizer::normalizeUrl($value);
    }
}
