<?php

namespace App\Modules\TelegramBot\Jobs\Middleware;

use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Jobs\BotJob;
use Closure;
use Illuminate\Support\Facades\Cache;

final class TelegramCooldown
{
    public function handle(BotJob $job, Closure $next): void
    {
        $remaining = (int) Cache::get('telegram-bot:cooldown', 0) - now()->timestamp;
        if ($remaining > 0) {
            $job->release($remaining);

            return;
        }
        try {
            $next($job);
        } catch (TelegramTransportException $exception) {
            if ($exception->apiCode !== 429) {
                throw $exception;
            }
            $delay = min((int) config('telegram_bot.queue.max_retry_after_seconds'), $exception->retryAfter ?? (int) config('telegram_bot.queue.default_retry_after_seconds'));
            Cache::put('telegram-bot:cooldown', now()->addSeconds($delay)->timestamp, $delay);
            $job->release($delay);
        }
    }
}
