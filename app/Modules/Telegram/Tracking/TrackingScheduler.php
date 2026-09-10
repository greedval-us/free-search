<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Tracking\Jobs\CollectTrackingSource;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class TrackingScheduler
{
    public function __construct(private TrackingConfig $config, private TrackingLifecycle $lifecycle) {}

    public function maintain(): void
    {
        TelegramTracking::query()->unfinished()->select('user_id')->distinct()->orderBy('user_id')
            ->lazyById($this->config->integer('dispatch_batch'), column: 'user_id')
            ->each(fn ($task) => $this->lifecycle->synchronize($task->user_id));
        TelegramTracking::query()->where('purge_at', '<=', now())->select('id')
            ->chunkById($this->config->integer('dispatch_batch'), fn ($tasks) => TelegramTracking::query()->whereKey($tasks->modelKeys())->delete());
    }

    public function dispatch(): int
    {
        $this->config->ensureQueue();
        $sources = TelegramTrackingSource::query()->where('next_check_at', '<=', now())->leaseAvailable()
            ->whereHas('tracking', fn ($query) => $query->active()->where('expires_at', '>', now()))
            ->orderBy('next_check_at')->limit($this->config->integer('dispatch_batch'))->get();
        $count = 0;
        foreach ($sources as $source) {
            $token = (string) Str::uuid();
            $claimed = TelegramTrackingSource::query()->whereKey($source->id)->leaseAvailable()
                ->update(['lease_token' => $token, 'lease_until' => now()->addSeconds($this->config->integer('lease_seconds'))]);
            if (! $claimed) {
                continue;
            }
            try {
                Bus::dispatch(new CollectTrackingSource($source->id, $token));
                $count++;
            } catch (Throwable $exception) {
                TelegramTrackingSource::query()->whereKey($source->id)->where('lease_token', $token)
                    ->update(['lease_token' => null, 'lease_until' => null]);
                Log::warning('Telegram tracking queue unavailable.', ['exception_class' => $exception::class]);
            }
        }

        return $count;
    }
}
