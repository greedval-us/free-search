<?php

namespace App\Modules\YouTube\Enums;

enum YouTubeDurationBucket: string
{
    case Short = 'short';
    case Medium = 'medium';
    case Long = 'long';

    public static function fromSeconds(int $seconds): self
    {
        return match (true) {
            $seconds < 240 => self::Short,
            $seconds <= 1200 => self::Medium,
            default => self::Long,
        };
    }

    /**
     * @return array<string, int>
     */
    public static function emptyDistribution(): array
    {
        return [
            self::Short->value => 0,
            self::Medium->value => 0,
            self::Long->value => 0,
        ];
    }
}

