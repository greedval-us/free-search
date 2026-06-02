<?php

namespace App\Modules\SiteIntel\Enums;

enum SiteIntelScoreLevel: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public static function fromThresholds(int $score, int $highThreshold, int $mediumThreshold): self
    {
        return match (true) {
            $score >= $highThreshold => self::High,
            $score >= $mediumThreshold => self::Medium,
            default => self::Low,
        };
    }
}
