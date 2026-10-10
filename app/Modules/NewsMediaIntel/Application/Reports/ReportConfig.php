<?php

namespace App\Modules\NewsMediaIntel\Application\Reports;

use App\Support\Reports\ReportQueueSafety;

final class ReportConfig
{
    public function __construct(private readonly ReportQueueSafety $queues) {}

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
        if (! $this->queues->supports((string) config('news_media_reports.queue.connection'),
            $this->integer('queue.timeout'), $this->integer('lease_seconds'))) {
            throw new ReportException('queue_unavailable');
        }
    }
}
