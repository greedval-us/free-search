<?php

namespace App\Modules\TelegramBot\Domain\DTO;

final readonly class BotScreen
{
    /** @param list<BotButton> $buttons */
    public function __construct(public string $text, public array $buttons = []) {}
}
