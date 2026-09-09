<?php

namespace App\Modules\TelegramBot\Domain\DTO;

final readonly class BotButton
{
    /** @param array<string, string|int> $parameters */
    public function __construct(
        public string $label,
        public string $type,
        public string $value,
        public array $parameters = [],
    ) {}
}
