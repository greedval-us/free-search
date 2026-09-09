<?php

namespace App\Modules\TelegramBot\Domain\Contracts;

use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;

interface BotAction
{
    public function key(): string;

    /** @param array<string, string> $parameters */
    public function handle(BotContext $context, array $parameters): BotScreen;
}
