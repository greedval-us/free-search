<?php

namespace App\Support\MadelineProto;

use Closure;
use danog\MadelineProto\RPCError\RateLimitError;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class MadelineProtoOperationGuard
{
    public function run(string $session, Closure $callback, int $gap = 0): mixed
    {
        // Retain Tracking's key so outstanding FLOOD_WAIT deadlines survive deployment.
        $key = 'telegram-tracking:session:'.$session;
        $lock = Cache::lock($key.':lock', max(60, (int) config('telegram_tracking.lease_seconds', 300)));
        if (! $lock->get()) {
            throw new SessionOperationException('busy', max(1, $gap));
        }
        try {
            $wait = (int) Cache::get($key.':until', 0) - now()->timestamp;
            if ($wait > 0) {
                throw new SessionOperationException('cooldown', $wait);
            }

            return $callback();
        } catch (Throwable $exception) {
            $wait = $exception instanceof RateLimitError ? max(1, $exception->getWaitTimeLeft()) : null;
            if ($wait === null && preg_match('/FLOOD_WAIT_(\d+)|A wait of (\d+) seconds is required/i', $exception->getMessage(), $match)) {
                $wait = max(1, (int) ($match[1] !== '' ? $match[1] : $match[2]));
            }
            if ($wait !== null) {
                Cache::put($key.':until', now()->timestamp + $wait, $wait);
            }
            throw $exception;
        } finally {
            if ($gap > 0 && (int) Cache::get($key.':until', 0) < now()->timestamp + $gap) {
                Cache::put($key.':until', now()->timestamp + $gap, $gap);
            }
            $lock->release();
        }
    }
}
