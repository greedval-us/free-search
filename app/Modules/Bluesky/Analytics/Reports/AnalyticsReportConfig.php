<?php

namespace App\Modules\Bluesky\Analytics\Reports;

use App\Support\Reports\ReportQueueSafety;

final class AnalyticsReportConfig
{
    public function __construct(private readonly ReportQueueSafety $queues) {}

    public function maxAccounts(): int
    {
        return $this->integer('max_accounts');
    }

    public function integer(string $key): int
    {
        return max(1, (int) config('bluesky_analytics_reports.'.$key));
    }

    public function ensureQueue(): void
    {
        if (! $this->queues->supports((string) config('bluesky_analytics_reports.queue.connection'),
            $this->integer('queue.timeout'), $this->integer('lease_seconds'))) {
            throw new AnalyticsReportException('queue_unavailable');
        }
    }
}
