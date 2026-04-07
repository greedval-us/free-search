<?php

namespace App\Modules\Telegram\DTO\Request;

class SearchParticipantsDTO
{
    public function __construct(
        public string $chatUsername,
        public string $filter = 'channelParticipantsRecent',
        public int $limit = 100,
        public int $offset = 0,
        public array $hash = []
    ) {}

    public static function fromArray(array $params): self
    {
        return new self(
            chatUsername: $params['chatUsername'] ?? $params['chat'] ?? '',
            filter: $params['filter'] ?? 'channelParticipantsRecent',
            limit: $params['limit'] ?? 100,
            offset: $params['offset'] ?? 0,
            hash: $params['hash'] ?? [],
        );
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
