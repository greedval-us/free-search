<?php

namespace App\Modules\SiteIntel\Application\Services\SeoAudit;

final class SeoAuditTechnicalThresholds
{
    public const int TITLE_MIN = 15;
    public const int TITLE_MAX = 65;
    public const int DESCRIPTION_MIN = 50;
    public const int DESCRIPTION_MAX = 170;
    public const int TTFB_SLOW_MS = 1200;
    public const int PAGE_SIZE_HEAVY_KB = 1500;
    public const int RENDER_BLOCKING_HIGH = 6;

    public function isTitleOutOfRange(int $length): bool
    {
        return $length < self::TITLE_MIN || $length > self::TITLE_MAX;
    }

    public function isDescriptionOutOfRange(int $length): bool
    {
        return $length < self::DESCRIPTION_MIN || $length > self::DESCRIPTION_MAX;
    }

    public function isSlowTtfb(int $ttfbMs): bool
    {
        return $ttfbMs > self::TTFB_SLOW_MS;
    }

    public function isHeavyPage(int $sizeKb): bool
    {
        return $sizeKb > self::PAGE_SIZE_HEAVY_KB;
    }

    public function hasHighRenderBlocking(int $total): bool
    {
        return $total > self::RENDER_BLOCKING_HIGH;
    }
}

