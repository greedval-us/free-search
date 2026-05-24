<?php

namespace App\Modules\YouTube\Support;

final class YouTubeModuleConfig
{
    public static function fromTimezone(string $timezone): self
    {
        return new self(
            timezone: trim($timezone) !== '' ? $timezone : 'UTC'
        );
    }

    public function __construct(private readonly string $timezone)
    {
    }

    public function timezone(): string
    {
        return $this->timezone;
    }
}
