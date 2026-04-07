<?php

namespace App\Modules\Telegram\DTO\Request;

class ParticipantsDTO
{
    public function __construct(
        public string $chatUsername,
        public string $filter,
        public int $limit = 200,
        public int $offset = 0,
        public array $hash = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'channel' => $this->chatUsername,
            'filter' => ['_' => $this->filter],
            'limit' => $this->limit,
            'offset' => $this->offset,
            'hash' => $this->hash,
        ];
    }
}
