<?php

namespace App\Modules\TelegramBot\Jobs;

use App\Modules\TelegramBot\Application\ArtifactRegistry;
use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotButton;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Infrastructure\NotificationText;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class DeliverBotMessage extends BotJob
{
    public function __construct(public int $deliveryId, string $telegramId)
    {
        parent::__construct($telegramId);
    }

    public function handle(BotConfig $config, BotAccess $access, BotTransport $transport,
        ArtifactRegistry $artifacts, TemporaryDocuments $files, NotificationText $notifications): void
    {
        if (! $config->active()) {
            return;
        }
        $delivery = BotDelivery::query()->with(['link.user', 'link.chat'])->find($this->deliveryId);
        if ($delivery === null || $delivery->status !== BotDelivery::PENDING) {
            return;
        }
        $link = $delivery->link;
        if (! $access->allowsDelivery($link, $delivery->kind, $delivery->automatic)) {
            $delivery->update(['status' => BotDelivery::SKIPPED]);

            return;
        }

        try {
            if (in_array($delivery->kind, ['parser', 'tracking'], true)) {
                $document = $artifacts->get($delivery->kind)->document($link->user_id, (int) $delivery->reference,
                    (string) ($delivery->payload['format'] ?? ''), $link->locale);
                try {
                    // Rendering can be slow. Recheck consent and ownership immediately before upload.
                    $current = BotLink::query()->with(['user', 'chat'])->find($link->id);
                    if (! $access->allowsDelivery($current, $delivery->kind, $delivery->automatic)) {
                        $delivery->update(['status' => BotDelivery::SKIPPED]);

                        return;
                    }
                    $transport->document($current->telegraph_chat_id, $document);
                } finally {
                    $files->remove($document);
                }
            } else {
                if ($delivery->kind === 'notification') {
                    $notification = $link->user->notifications()->find($delivery->reference);
                    if ($notification === null) {
                        $delivery->update(['status' => BotDelivery::SKIPPED]);

                        return;
                    }
                    $text = $notifications->render($notification->data, $link->locale);
                } else {
                    $text = (string) ($delivery->payload['message'] ?? '');
                }
                $transport->message($link->telegraph_chat_id, new BotScreen($text, [
                    new BotButton(__('telegram_bot.menu.webapp', [], $link->locale), 'url', $config->siteUrl()),
                ]));
            }
            $delivery->update(['status' => BotDelivery::SENT, 'sent_at' => now(), 'error_code' => null]);
        } catch (ArtifactUnavailable $exception) {
            $this->unavailable($delivery, $exception->reason, $transport, $config, $access);
        } catch (HttpExceptionInterface $exception) {
            if (! in_array($exception->getStatusCode(), [404, 410], true)) {
                throw $exception;
            }
            $this->unavailable($delivery, 'file_unavailable', $transport, $config, $access);
        } catch (TelegramTransportException $exception) {
            if (in_array($exception->apiCode, [400, 403], true)) {
                $delivery->update(['status' => BotDelivery::FAILED, 'error_code' => 'telegram_'.$exception->apiCode]);
            } else {
                throw $exception;
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        BotDelivery::query()->whereKey($this->deliveryId)->where('status', BotDelivery::PENDING)
            ->update(['status' => BotDelivery::FAILED, 'error_code' => 'delivery_failed']);
    }

    private function unavailable(BotDelivery $delivery, string $reason, BotTransport $transport, BotConfig $config, BotAccess $access): void
    {
        $link = BotLink::query()->with(['user', 'chat'])->find($delivery->link_id);
        if ($access->allowsDelivery($link, $delivery->kind, $delivery->automatic)) {
            $transport->message($link->telegraph_chat_id, new BotScreen(__('telegram_bot.errors.'.$reason, [], $link->locale), [
                new BotButton(__('telegram_bot.menu.webapp', [], $link->locale), 'url', $config->siteUrl()),
            ]));
        }
        $delivery->update(['status' => BotDelivery::SKIPPED, 'error_code' => $reason]);
    }
}
