<?php

namespace App\Modules\SiteIntel\Application\Reports;

use App\Support\Reports\ReportQueueSafety;

final class ReportConfig
{
    public function __construct(private readonly ReportQueueSafety $queues) {}

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
        if (! $this->queues->supports((string) config('site_intel_reports.queue.connection'),
            $this->integer('queue.timeout'), $this->integer('lease_seconds'))) {
            throw new ReportException('queue_unavailable');
        }
    }
}
