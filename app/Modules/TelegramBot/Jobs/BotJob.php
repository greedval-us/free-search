<?php

namespace App\Modules\TelegramBot\Jobs;

use App\Modules\TelegramBot\Jobs\Middleware\TelegramCooldown;
use App\Modules\TelegramBot\Support\BotConfig;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;

abstract class BotJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public int $maxExceptions;

    public int $timeout;

    public int $deadline;

    public function __construct(public string $telegramId)
    {
        $config = app(BotConfig::class);
        $this->maxExceptions = $config->integer('queue.max_exceptions');
        $this->timeout = $config->integer('queue.timeout');
        $this->deadline = now()->addMinutes($config->integer('queue.retry_window_minutes'))->timestamp;
        $this->onConnection($config->get('queue.connection'));
        $this->onQueue($config->get('queue.name'));
    }

    public function retryUntil(): DateTimeInterface
    {
        return CarbonImmutable::createFromTimestamp($this->deadline);
    }

    public function backoff(): array
    {
        return config('telegram_bot.queue.backoff');
    }

    public function middleware(): array
    {
        return [
            new TelegramCooldown,
            (new WithoutOverlapping('telegram-bot:'.$this->telegramId))->shared()
                ->releaseAfter((int) config('telegram_bot.queue.overlap_release_seconds'))
                ->expireAfter($this->timeout + (int) config('telegram_bot.queue.lock_grace_seconds')),
            new RateLimited('telegram-bot'),
        ];
    }
}
