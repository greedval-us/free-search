<?php

namespace App\Jobs\Monitoring;

use App\Services\Monitoring\MonitoringCollector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CollectMonitoringSource implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(public int $collectionId, public int $ownerId) {}

    public function handle(MonitoringCollector $service): void
    {
        $service->collect($this->collectionId, $this->ownerId);
    }
}
