<?php

namespace App\Modules\Telegram\Tracking;

use App\Support\Access\Enums\AccountPlan;

final class TrackingConfig
{
    private const MAX_PAGE_SIZE = 100;

    public function pageSize(): int
    {
        return min(self::MAX_PAGE_SIZE, $this->integer('page_size'));
    }

    public function integer(string $key): int
    {
        return max(1, (int) config('telegram_tracking.'.$key));
    }

    public function limit(AccountPlan $plan): int
    {
        return $this->integer('limits.'.$plan->value);
    }

    public function interval(int $sources = 0): int
    {
        return min($this->integer('max_interval_hours'), $this->integer('interval_hours')
            * max(1, (int) ceil($sources / $this->integer('sources_per_interval'))));
    }

    public function ensureQueue(): void
    {
        $connection = config('telegram_tracking.queue.connection');
        $driver = config('queue.connections.'.$connection.'.driver');
        $retryAfter = config('queue.connections.'.$connection.'.retry_after');
        if (! in_array($driver, ['database', 'redis', 'beanstalkd'], true)
            || ! is_numeric($retryAfter) || $retryAfter <= $this->integer('queue.timeout')
            || $this->integer('lease_seconds') <= $this->integer('queue.timeout')
            || (app()->isProduction() && config('cache.stores.'.config('cache.default').'.driver') === 'array')) {
            throw new TrackingException('queue_unavailable');
        }
    }
}
