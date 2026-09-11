<?php

namespace App\Modules\TelegramBot\Infrastructure;

use App\Models\ParserRun;
use App\Models\User;
use App\Modules\ParserSupport\Enums\ParserRunStatus;
use App\Modules\TelegramBot\Application\DeliveryOutbox;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use Closure;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class BotEventSubscriber
{
    public function __construct(private BotConfig $config, private DeliveryOutbox $outbox) {}

    public function notificationSent(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->notifiable instanceof User || ! $event->response instanceof DatabaseNotification) {
            return;
        }
        $this->safely(function () use ($event): void {
            if (($event->response->data['telegram_bot'] ?? true) === false) {
                return;
            }
            $link = BotLink::query()->where('user_id', $event->notifiable->id)->first();
            if ($link !== null) {
                $this->outbox->enqueue($link, 'notification', $event->response->id);
            }
        });
    }

    public function parserSaved(ParserRun $run): void
    {
        if ($run->status !== ParserRunStatus::Completed->value || (! $run->wasRecentlyCreated && ! $run->wasChanged('status'))) {
            return;
        }
        $this->safely(function () use ($run): void {
            $link = BotLink::query()->where('user_id', $run->user_id)->first();
            if ($link !== null) {
                $this->outbox->enqueue($link, 'parser', (string) $run->id, ['format' => 'xlsx']);
            }
        });
    }

    private function safely(Closure $callback): void
    {
        if (! $this->config->active()) {
            return;
        }
        try {
            $callback();
        } catch (Throwable $exception) {
            // An optional delivery channel must not break the website's main operation.
            Log::warning('Telegram bot integration unavailable.', ['exception_class' => $exception::class]);
        }
    }
}
