<?php

namespace App\Modules\Telegram\Search\DTO;

class SearchCommentsQueryDTO
{
    public function __construct(
        public readonly string $chatUsername,
        public readonly int $postId,
        public readonly int $limit,
        public readonly int $offsetId,
    ) {
    }
}
