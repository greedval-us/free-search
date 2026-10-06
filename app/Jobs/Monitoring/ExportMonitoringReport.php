<?php

namespace App\Jobs\Monitoring;

use App\Services\Monitoring\MonitoringExports;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExportMonitoringReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $reportId, public int $ownerId) {}

    public function handle(MonitoringExports $service): void
    {
        $service->prepare($this->reportId, $this->ownerId);
    }
}
