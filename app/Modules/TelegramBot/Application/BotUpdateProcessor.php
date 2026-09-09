<?php

namespace App\Modules\TelegramBot\Application;

use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotButton;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Domain\DTO\BotUpdate;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use DefStudio\Telegraph\Models\TelegraphBot;

final readonly class BotUpdateProcessor
{
    public function __construct(private BotConfig $config, private BotTransport $transport, private BotAccess $access,
        private AccountLinkService $links, private BotRouter $router) {}

    public function process(BotUpdate $update): void
    {
        $chat = TelegraphBot::query()->findOrFail($this->config->botId())->chats()
            ->firstOrCreate(['chat_id' => $update->telegramId], ['name' => 'Private chat']);
        $link = BotLink::query()->with(['user', 'chat'])->where('telegraph_chat_id', $chat->id)->first();
        $allowed = $this->access->allows($link);
        $locale = $this->config->locale($allowed ? $link->locale : $update->locale);
        $context = new BotContext($chat->id, $update->telegramId, $locale, (string) $update->id,
            $allowed ? $link->id : null, $allowed ? $link->user_id : null);

        if ($update->callbackId !== null) {
            try {
                $this->transport->acknowledge($update->callbackId);
            } catch (TelegramTransportException) {
                // Telegram can expire the callback while the queue is busy.
            }
        }
        if ($update->callbackId === null && preg_match('/^\/start(?:@[A-Za-z0-9_]+)?\s+([A-Za-z0-9]{48})$/D', trim($update->text), $matches)) {
            $claimed = $this->links->claim($matches[1], $update->telegramId, $chat->id);
            $screen = new BotScreen(__('telegram_bot.screens.'.($claimed ? 'confirm_on_site' : 'invalid_link'), [], $locale), [
                new BotButton(__('telegram_bot.menu.settings', [], $locale), 'url', $this->config->siteUrl('/settings/telegram')),
            ]);
        } else {
            $parameters = [];
            foreach (explode(';', $update->callbackData) as $part) {
                $pair = explode(':', $part, 2);
                if (count($pair) === 2 && preg_match('/^[a-z]+$/D', $pair[0])) {
                    $parameters[$pair[0]] = $pair[1];
                }
            }
            try {
                $screen = $this->router->dispatch($parameters['action'] ?? 'menu', $context, $parameters);
            } catch (ArtifactUnavailable $exception) {
                $screen = new BotScreen(__('telegram_bot.errors.'.$exception->reason, [], $locale));
            }
        }
        $this->transport->message($chat->id, $screen);
    }
}
