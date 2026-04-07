<?php

namespace App\Modules\Telegram\DTO\Request;

class SearchMessagesDTO
{
    public function __construct(
        public string $peer,
        public string $q = '',
        public int|string|null $from_id = null,
        public $saved_peer_id = null,
        public array $saved_reaction = [],
        public ?int $top_msg_id = null,
        public array $filter = ['_' => 'inputMessagesFilterEmpty'],
        public int $min_date = 0,
        public int $max_date = 0,
        public int $offset_id = 0,
        public int $add_offset = 0,
        public int $limit = 20,
        public int $max_id = 0,
        public int $min_id = 0,
        public array $hash = []
    ) {}

    public static function fromArray(array $params): self
    {
        return new self(
            peer: $params['peer'] ?? $params['chatUsername'] ?? $params['chat'] ?? '',
            q: $params['q'] ?? '',
            from_id: $params['from_id'] ?? null,
            saved_peer_id: $params['saved_peer_id'] ?? null,
            saved_reaction: $params['saved_reaction'] ?? [],
            top_msg_id: $params['top_msg_id'] ?? null,
            filter: $params['filter'] ?? ['_' => 'inputMessagesFilterEmpty'],
            min_date: $params['min_date'] ?? 0,
            max_date: $params['max_date'] ?? 0,
            offset_id: $params['offset_id'] ?? 0,
            add_offset: $params['add_offset'] ?? 0,
            limit: $params['limit'] ?? 20,
            max_id: $params['max_id'] ?? 0,
            min_id: $params['min_id'] ?? 0,
            hash: $params['hash'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'peer' => $this->peer,
            'q' => $this->q,
            'from_id' => $this->from_id,
            'saved_peer_id' => $this->saved_peer_id,
            'saved_reaction' => $this->saved_reaction,
            'top_msg_id' => $this->top_msg_id,
            'filter' => $this->filter,
            'min_date' => $this->min_date,
            'max_date' => $this->max_date,
            'offset_id' => $this->offset_id,
            'add_offset' => $this->add_offset,
            'limit' => $this->limit,
            'max_id' => $this->max_id,
            'min_id' => $this->min_id,
            'hash' => $this->hash,
        ];
    }
}
