<?php

namespace App\Jobs\Monitoring;

use App\Services\Monitoring\MonitoringCollector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ValidateMonitoringSource implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $sourceId, public int $ownerId, public int $generation, public int $projectGeneration) {}

    public function handle(MonitoringCollector $service): void
    {
        $service->validate($this->sourceId, $this->ownerId, $this->generation, $this->projectGeneration);
    }
}
