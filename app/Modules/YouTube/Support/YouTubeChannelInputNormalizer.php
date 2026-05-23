<?php

namespace App\Modules\YouTube\Support;

class YouTubeChannelInputNormalizer
{
    public function looksLikeChannelId(string $value): bool
    {
        return preg_match('/^UC[A-Za-z0-9_-]{20,}$/', trim($value)) === 1;
    }

    public function normalizeHandle(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return str_starts_with($value, '@') ? $value : '@'.$value;
    }

    public function normalizeUsername(string $value): string
    {
        return ltrim(trim($value), '@');
    }
}
