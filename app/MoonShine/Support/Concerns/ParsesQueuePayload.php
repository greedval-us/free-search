<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Concerns;

trait ParsesQueuePayload
{
    protected static function resolveJobDisplayName(string $payload): string
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return __('admin_panel.values.unknown');
        }

        $display = $decoded['displayName'] ?? $decoded['job'] ?? null;
        if (!is_string($display) || $display === '') {
            return __('admin_panel.values.unknown');
        }

        return $display;
    }

    protected static function resolveExceptionSummary(string $exception): string
    {
        $line = strtok($exception, "\n");
        if ($line === false || $line === '') {
            return __('admin_panel.values.unknown');
        }

        return mb_strimwidth($line, 0, 140, '...');
    }
}
