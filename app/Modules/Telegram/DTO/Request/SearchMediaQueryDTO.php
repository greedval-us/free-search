<?php

namespace App\Modules\Telegram\DTO\Request;

class SearchMediaQueryDTO
{
    public function __construct(
        public readonly string $chatUsername,
        public readonly int $messageId,
    ) {
    }
}

