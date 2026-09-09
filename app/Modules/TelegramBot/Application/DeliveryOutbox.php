<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Jobs\DeliverBotMessage;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class DeliveryOutbox
{
    public function __construct(private BotConfig $config, private BotAccess $access) {}

    /** @param array<string, mixed> $payload */
    public function enqueue(BotLink $link, string $kind, string $reference, array $payload = [], bool $automatic = true, ?string $requestId = null): bool
    {
        if (! $this->access->allows($link) || ! $this->subscribed($link, $kind, $automatic)) {
            return false;
        }
        $delivery = BotDelivery::query()->firstOrCreate([
            'deduplication_key' => hash('sha256', implode(':', [$link->id, $kind, $reference, $requestId ?? 'automatic'])),
        ], ['link_id' => $link->id, 'kind' => $kind, 'reference' => $reference, 'payload' => $payload, 'automatic' => $automatic]);
        if ($delivery->status === BotDelivery::PENDING && $delivery->dispatched_at === null) {
            $this->dispatch($delivery, $link->telegram_id);
        }

        return true;
    }

    public function subscribed(BotLink $link, string $kind, bool $automatic): bool
    {
        return match ($kind) {
            'notification' => $link->notifications_enabled,
            'broadcast' => $link->broadcasts_enabled,
            'parser' => ! $automatic || $link->exports_enabled,
            default => false,
        };
    }

    public function retryPending(): void
    {
        if (! $this->config->active()) {
            return;
        }
        BotDelivery::query()->where('status', BotDelivery::PENDING)
            ->where(fn ($query) => $query->whereNull('dispatched_at')->orWhere('dispatched_at', '<=', now()->subSeconds($this->config->integer('queue.dispatch_retry_seconds'))))
            ->with('link')->chunkById($this->config->integer('batch_size'), function ($deliveries): void {
                foreach ($deliveries as $delivery) {
                    if ($delivery->link !== null) {
                        $this->dispatch($delivery, $delivery->link->telegram_id);
                    }
                }
            });
    }

    private function dispatch(BotDelivery $delivery, string $telegramId): void
    {
        DB::afterCommit(function () use ($delivery, $telegramId): void {
            try {
                Bus::dispatch(new DeliverBotMessage($delivery->id, $telegramId));
                $delivery->update(['dispatched_at' => now()]);
            } catch (Throwable $exception) {
                // The committed outbox entry can be retried by the scheduler.
                Log::warning('Telegram bot queue unavailable.', ['delivery_id' => $delivery->id, 'exception_class' => $exception::class]);
            }
        });
    }
}
