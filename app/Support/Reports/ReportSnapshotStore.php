<?php

namespace App\Support\Reports;

use App\Support\Reports\Events\ReportSnapshotStored;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

final readonly class ReportSnapshotStore
{
    public function __construct(
        private CacheRepository $cache,
        private ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function store(int $userId, string $feature, array $parameters, array $report): array
    {
        $ttl = max(1, (int) $this->config->get('access.report_snapshot_ttl_seconds', 3600));
        $this->cache->put(
            $this->key($userId, $feature, $parameters),
            $report,
            $ttl,
        );

        event(new ReportSnapshotStored($userId, $feature, $parameters, now()->addSeconds($ttl)));

        return $report;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function get(int $userId, string $feature, array $parameters): array
    {
        $report = $this->cache->get($this->key($userId, $feature, $parameters));
        if (! is_array($report)) {
            throw new GoneHttpException(__('errors.api.report_expired'));
        }

        return $report;
    }

    /** @param array<string, mixed> $parameters */
    private function key(int $userId, string $feature, array $parameters): string
    {
        ksort($parameters);

        return sprintf('report-snapshot:v1:%d:%s:%s', $userId, $feature, hash('sha256', json_encode($parameters, JSON_THROW_ON_ERROR)));
    }
}
