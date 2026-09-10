<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTrackingMessage;
use App\Models\TelegramTrackingSource;
use App\Models\User;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class TrackingCollector
{
    public function __construct(
        private TrackingGateway $gateway,
        private TrackingConfig $config,
        private TrackingLifecycle $lifecycle,
        private TrackingPageProcessor $pages,
        private TrackingNotifications $notifications,
    ) {}

    public function collect(int $sourceId, string $token): void
    {
        $lock = Cache::lock('telegram-tracking:source:'.$sourceId, $this->config->integer('lease_seconds'));
        if (! $lock->get()) {
            return;
        }
        try {
            $source = TelegramTrackingSource::query()->with('tracking')->find($sourceId);
            if ($source === null || $source->lease_token !== $token) {
                return;
            }
            $this->lifecycle->synchronize($source->tracking->user_id);
            $source->refresh();
            if (! $source->isClaimedBy($token)) {
                return;
            }
            if ($source->window_end === null) {
                $initialized = TelegramTrackingSource::query()->whereKey($sourceId)->where('lease_token', $token)
                    ->update(['window_end' => now(), 'offset_id' => 0, 'high_id' => $source->cursor_id]);
                if (! $initialized) {
                    return;
                }
                $source->refresh();
            }
            $messages = $this->gateway->history($source);
            $this->persist($source, $token, $messages);
        } catch (TrackingException $exception) {
            $this->retry($sourceId, $token, $exception);
        } catch (Throwable $exception) {
            Log::warning('Telegram tracking collection failed.', ['source_id' => $sourceId, 'exception_class' => $exception::class]);
            $this->retry($sourceId, $token, new TrackingException('collection_failed'));
        } finally {
            $lock->release();
        }
    }

    private function persist(TelegramTrackingSource $snapshot, string $token, array $messages): void
    {
        DB::transaction(function () use ($snapshot, $token, $messages): void {
            $user = User::query()->lockForUpdate()->find($snapshot->tracking->user_id);
            if ($user === null) {
                return;
            }
            $this->lifecycle->synchronizeLocked($user);
            $source = TelegramTrackingSource::query()->with('tracking')->lockForUpdate()->find($snapshot->id);
            if ($source === null || ! $source->isClaimedBy($token)) {
                return;
            }
            $page = $this->pages->process($source, $messages);
            $pending = $source->pending_matches + $this->storeMatches($source, $page);
            $this->checkpoint($source, $page, $pending);
            if ($page->complete) {
                $this->notifications->matches($user, $source, $pending);
            }
        });
    }

    private function storeMatches(TelegramTrackingSource $source, TrackingPage $page): int
    {
        $found = 0;
        foreach ($page->matches as $match) {
            $row = TelegramTrackingMessage::query()->firstOrCreate([
                'source_id' => $source->id, 'message_id' => $match['message_id'],
            ], [
                'tracking_id' => $source->tracking_id,
                'sender_id' => $match['sender_id'], 'text' => $match['text'],
                'sent_at' => CarbonImmutable::createFromTimestampUTC($match['sent_at']), 'received_at' => now(),
            ]);
            $found += (int) $row->wasRecentlyCreated;
        }

        return $found;
    }

    private function checkpoint(TelegramTrackingSource $source, TrackingPage $page, int $pending): void
    {
        $nextCheck = now()->addSeconds($this->config->integer('request_gap_seconds'));
        if ($page->complete) {
            $load = TelegramTrackingSource::query()->where('session_name', $source->session_name)
                ->whereHas('tracking', fn ($query) => $query->active())->count();
            $nextCheck = now()->addHours($this->config->interval($load));
        }
        $source->update([
            'cursor_id' => $page->complete ? $page->highId : $source->cursor_id,
            'offset_id' => $page->complete ? 0 : $page->offsetId,
            'high_id' => $page->complete ? 0 : $page->highId,
            'checked_at' => $page->complete ? $source->window_end : $source->checked_at,
            'window_end' => $page->complete ? null : $source->window_end,
            'pending_matches' => $page->complete ? 0 : $pending,
            'lease_token' => null, 'lease_until' => null, 'error_code' => null, 'failure_count' => 0,
            'next_check_at' => $nextCheck,
        ]);
    }

    private function retry(int $sourceId, string $token, TrackingException $exception): void
    {
        $source = TelegramTrackingSource::query()->where('lease_token', $token)->find($sourceId);
        if ($source === null) {
            return;
        }
        $failures = min($source->failure_count + 1, $this->config->integer('max_failure_exponent'));
        $seconds = $exception->retryAfter > 0 ? $exception->retryAfter
            : min($this->config->integer('max_interval_hours') * 3600, $this->config->integer('retry_seconds') * (2 ** ($failures - 1)));
        // Telegram's mandatory cooldown is never shortened to our scheduling target.
        TelegramTrackingSource::query()->whereKey($sourceId)->where('lease_token', $token)->update([
            'error_code' => $exception->reason, 'failure_count' => $failures,
            'next_check_at' => now()->addSeconds($seconds), 'lease_token' => null, 'lease_until' => null,
        ]);
    }
}
