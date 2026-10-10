<?php

namespace App\Modules\Telegram\Analytics\Reports;

use App\Support\Reports\ReportQueueSafety;

final class AnalyticsReportConfig
{
    public function __construct(private readonly ReportQueueSafety $queues) {}

    public function integer(string $key): int
    {
        return max(1, (int) config('telegram_analytics_reports.'.$key));
    }

    public function ensureQueue(): void
    {
        if (! $this->queues->supports((string) config('telegram_analytics_reports.queue.connection'),
            $this->integer('queue.timeout'), $this->integer('lease_seconds'))) {
            throw new AnalyticsReportException('queue_unavailable');
        }
    }
}
