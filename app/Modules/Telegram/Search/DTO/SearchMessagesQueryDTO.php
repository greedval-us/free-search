<?php

namespace App\Modules\Telegram\Search\DTO;

class SearchMessagesQueryDTO
{
    /**
     * @param array<string, mixed> $filter
     */
    public function __construct(
        public readonly array $filter,
        public readonly int $limit,
        public readonly int $offsetId,
        public readonly string $chatUsername,
    ) {
    }
}
