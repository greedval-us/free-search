<?php

namespace App\Modules\Telegram\DTO\Request;

class SearchMessagesDTO
{
    public function __construct(
        public string $peer,
        public string $q = '',
        public int|string|array|null $from_id = null,
        public int|string|array|null $saved_peer_id = null,
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
        $peer = (string) ($params['peer'] ?? $params['chatUsername'] ?? $params['chat'] ?? '');
        $peer = trim($peer);
        if ($peer === '') {
            throw new \InvalidArgumentException('Messages search requires non-empty "peer" or "chatUsername".');
        }

        $limit = (int) ($params['limit'] ?? 20);
        $limit = max(1, min($limit, 100));

        $offsetId = max(0, (int) ($params['offset_id'] ?? 0));
        $addOffset = max(0, (int) ($params['add_offset'] ?? 0));
        $maxId = max(0, (int) ($params['max_id'] ?? 0));
        $minId = max(0, (int) ($params['min_id'] ?? 0));
        $minDate = max(0, (int) ($params['min_date'] ?? 0));
        $maxDate = max(0, (int) ($params['max_date'] ?? 0));

        if ($maxDate > 0 && $minDate > $maxDate) {
            throw new \InvalidArgumentException('"min_date" must be less than or equal to "max_date".');
        }

        return new self(
            peer: $peer,
            q: $params['q'] ?? '',
            from_id: $params['from_id'] ?? null,
            saved_peer_id: $params['saved_peer_id'] ?? null,
            saved_reaction: $params['saved_reaction'] ?? [],
            top_msg_id: $params['top_msg_id'] ?? null,
            filter: $params['filter'] ?? ['_' => 'inputMessagesFilterEmpty'],
            min_date: $minDate,
            max_date: $maxDate,
            offset_id: $offsetId,
            add_offset: $addOffset,
            limit: $limit,
            max_id: $maxId,
            min_id: $minId,
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
