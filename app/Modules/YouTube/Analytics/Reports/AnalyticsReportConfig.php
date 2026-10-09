<?php

namespace App\Modules\YouTube\Analytics\Reports;

final class AnalyticsReportConfig
{
    public function integer(string $key): int
    {
        return max(1, (int) config('youtube_analytics_reports.'.$key));
    }

    public function ensureQueue(): void
    {
        $connection = config('youtube_analytics_reports.queue.connection');
        $driver = config('queue.connections.'.$connection.'.driver');
        $retryAfter = config('queue.connections.'.$connection.'.retry_after');
        if (! in_array($driver, ['database', 'redis'], true)
            || ! is_numeric($retryAfter) || $retryAfter <= $this->integer('queue.timeout')
            || $this->integer('lease_seconds') <= $this->integer('queue.timeout')
            || (app()->isProduction() && config('cache.stores.'.config('cache.default').'.driver') === 'array')) {
            throw new AnalyticsReportException('queue_unavailable');
        }
    }
}
