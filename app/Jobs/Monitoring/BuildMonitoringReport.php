<?php

namespace App\Jobs\Monitoring;

use App\Services\Monitoring\MonitoringReports;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class BuildMonitoringReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $reportId, public int $ownerId) {}

    public function handle(MonitoringReports $service): void
    {
        $service->build($this->reportId, $this->ownerId);
    }
}
