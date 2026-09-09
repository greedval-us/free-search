<?php

namespace App\Modules\TelegramBot\Domain\Contracts;

use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;

interface BotTransport
{
    public function message(int $chatId, BotScreen $screen): void;

    public function document(int $chatId, BotDocument $document): void;

    public function acknowledge(string $callbackId): void;
}
