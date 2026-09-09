<?php

namespace App\Modules\TelegramBot\Infrastructure;

use App\Modules\TelegramBot\Domain\Contracts\BotTransport;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Domain\Exceptions\TelegramTransportException;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Modules\TelegramBot\Support\TelegramLimits;
use Closure;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Telegraph;
use Throwable;

final readonly class TelegraphTransport implements BotTransport
{
    public function __construct(private BotConfig $config) {}

    public function message(int $chatId, BotScreen $screen): void
    {
        $this->send(function () use ($chatId, $screen): Telegraph {
            $keyboard = Keyboard::make();
            foreach ($screen->buttons as $definition) {
                $button = Button::make($definition->label);
                if ($definition->type === 'action') {
                    $button->action($definition->value);
                    foreach ($definition->parameters as $key => $value) {
                        $button->param($key, $value);
                    }
                    if (strlen($button->toArray()['callback_data']) > TelegramLimits::CALLBACK_BYTES) {
                        throw new \LengthException('Telegram callback data exceeds 64 bytes.');
                    }
                } elseif ($definition->type === 'webapp') {
                    $button->webApp($definition->value);
                } else {
                    $button->url($definition->value);
                }
                $keyboard->row([$button]);
            }

            return $this->chat($chatId)->html(e(mb_substr($screen->text, 0, TelegramLimits::MESSAGE_CHARACTERS)))->keyboard($keyboard);
        });
    }

    public function document(int $chatId, BotDocument $document): void
    {
        $this->send(fn (): Telegraph => $this->chat($chatId)->document($document->path, $document->filename));
    }

    public function acknowledge(string $callbackId): void
    {
        $this->send(fn (): Telegraph => TelegraphBot::query()->findOrFail($this->config->botId())
            ->withEndpoint('answerCallbackQuery')->withData('callback_query_id', $callbackId));
    }

    private function chat(int $id): TelegraphChat
    {
        return TelegraphChat::query()->where('telegraph_bot_id', $this->config->botId())->findOrFail($id);
    }

    /** @param Closure(): Telegraph $request */
    private function send(Closure $request): void
    {
        if (! $this->config->active()) {
            throw new TelegramTransportException;
        }
        try {
            $response = $request()->send();
            if ($response->failed() || $response->telegraphError()) {
                $retryAfter = $response->json('parameters.retry_after');
                throw new TelegramTransportException((int) $response->json('error_code'),
                    is_numeric($retryAfter) ? max(1, (int) $retryAfter) : null);
            }
        } catch (TelegramTransportException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new TelegramTransportException;
        }
    }
}
