<?php

namespace App\Modules\TelegramBot\Domain\DTO;

final readonly class BotContext
{
    public function __construct(
        public int $chatId,
        public string $telegramId,
        public string $locale,
        public string $requestId,
        public ?int $linkId = null,
        public ?int $userId = null,
    ) {}
}
