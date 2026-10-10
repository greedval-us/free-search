<?php

namespace App\Support\Reports;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

final readonly class ReportQueueSafety
{
    private const DURABLE_DRIVERS = ['database', 'redis'];

    public function __construct(private Repository $config, private Application $app) {}

    public function supports(string $connection, int $timeoutSeconds, int $leaseSeconds): bool
    {
        $driver = $this->config->get('queue.connections.'.$connection.'.driver');
        $retryAfter = $this->config->get('queue.connections.'.$connection.'.retry_after');
        $cacheStore = $this->config->get('cache.default');
        $cacheDriver = $this->config->get('cache.stores.'.$cacheStore.'.driver');

        return in_array($driver, self::DURABLE_DRIVERS, true)
            && is_numeric($retryAfter) && $retryAfter > $timeoutSeconds
            && $leaseSeconds > $timeoutSeconds
            && (! $this->app->environment('production') || $cacheDriver !== 'array');
    }
}
