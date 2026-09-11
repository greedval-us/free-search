<?php

namespace App\Modules\Telegram\Tracking\Jobs;

use App\Modules\Telegram\Tracking\TrackingCollector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CollectTrackingSource implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $sourceId, public readonly string $token)
    {
        $this->timeout = (int) config('telegram_tracking.queue.timeout');
        $this->onConnection(config('telegram_tracking.queue.connection'));
        $this->onQueue(config('telegram_tracking.queue.name'));
    }

    public function handle(TrackingCollector $collector): void
    {
        $collector->collect($this->sourceId, $this->token);
    }
}
