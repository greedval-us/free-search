<?php

namespace App\Modules\SiteIntel\Application\Reports;

final class ReportConfig
{
    public function maxTargets(): int
    {
        return $this->integer('max_targets');
    }

    public function integer(string $key): int
    {
        return max(1, (int) config('site_intel_reports.'.$key));
    }

    public function ensureQueue(): void
    {
        $connection = config('site_intel_reports.queue.connection');
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
