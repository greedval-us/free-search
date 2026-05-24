<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Formatting;

final class AdminPanelDateFormatter
{
    public const DATE_TIME_FORMAT = 'd.m.Y H:i:s';

    public function formatUnixTimestamp(?int $timestamp): string
    {
        if ($timestamp === null || $timestamp <= 0) {
            return __('admin_panel.values.not_available');
        }

        return date(self::DATE_TIME_FORMAT, $timestamp);
    }
}

