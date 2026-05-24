<?php

declare(strict_types=1);

namespace App\MoonShine\Support\Formatting;

final class RequestLogBadgeResolver
{
    public function statusColor(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }

        if ($statusCode >= 400) {
            return 'warning';
        }

        if ($statusCode >= 200 && $statusCode < 400) {
            return 'success';
        }

        return 'gray';
    }

    public function responseTimeColor(float $responseTime): string
    {
        if ($responseTime >= 3000) {
            return 'error';
        }

        if ($responseTime >= 1500) {
            return 'warning';
        }

        if ($responseTime > 0) {
            return 'success';
        }

        return 'gray';
    }
}

