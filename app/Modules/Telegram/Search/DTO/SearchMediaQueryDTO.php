<?php

namespace App\Modules\Telegram\Search\DTO;

class SearchMediaQueryDTO
{
    public function __construct(
        public readonly string $chatUsername,
        public readonly int $messageId,
    ) {
    }
}
