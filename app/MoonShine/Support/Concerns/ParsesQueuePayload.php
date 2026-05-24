<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Concerns;

trait ParsesQueuePayload
{
    protected static function resolveJobDisplayName(string $payload): string
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return 'unknown';
        }

        $display = $decoded['displayName'] ?? $decoded['job'] ?? null;
        if (!is_string($display) || $display === '') {
            return 'unknown';
        }

        return $display;
    }

    protected static function resolveExceptionSummary(string $exception): string
    {
        $line = strtok($exception, "\n");
        if ($line === false || $line === '') {
            return 'unknown';
        }

        return mb_strimwidth($line, 0, 140, '...');
    }
}
