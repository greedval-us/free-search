<?php

namespace App\Modules\TelegramBot\Domain\DTO;

final readonly class BotDocument
{
    public function __construct(public string $path, public string $filename) {}
}
