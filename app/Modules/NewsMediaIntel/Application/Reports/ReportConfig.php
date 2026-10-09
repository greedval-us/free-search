<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

final class ReportConfig
{
    public function maxQueries(): int
    {
        return min(3, $this->integer('max_queries'));
    }

    public function integer(string $key): int
    {
        return max(1, (int) config('news_media_reports.'.$key));
    }

    public function ensureQueue(): void
    {
        $connection = config('news_media_reports.queue.connection');
        $driver = config('queue.connections.'.$connection.'.driver');
        $retryAfter = config('queue.connections.'.$connection.'.retry_after');
        if (! in_array($driver, ['database', 'redis'], true)
            || ! is_numeric($retryAfter) || $retryAfter <= $this->integer('queue.timeout')
            || $this->integer('lease_seconds') <= $this->integer('queue.timeout')
            || (app()->isProduction() && config('cache.stores.'.config('cache.default').'.driver') === 'array')) {
            throw new ReportException('queue_unavailable');
        }
    }
}
