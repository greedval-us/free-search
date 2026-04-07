<?php

namespace App\Modules\Telegram\DTO\Request;

class SearchParticipantsDTO
{
    private const DEFAULT_FILTER = 'channelParticipantsRecent';
    private const ALLOWED_FILTERS = [
        'channelParticipantsRecent',
        'channelParticipantsAdmins',
        'channelParticipantsBots',
        'channelParticipantsBanned',
        'channelParticipantsKicked',
        'channelParticipantsContacts',
        'channelParticipantsMentions',
        'channelParticipantsSearch',
    ];

    public function __construct(
        public string $chatUsername,
        public string $filter = self::DEFAULT_FILTER,
        public int $limit = 100,
        public int $offset = 0,
        public array $hash = []
    ) {}

    public static function fromArray(array $params): self
    {
        $chatUsername = trim((string) ($params['chatUsername'] ?? $params['chat'] ?? ''));
        if ($chatUsername === '') {
            throw new \InvalidArgumentException('Participants search requires non-empty "chatUsername" or "chat".');
        }

        $filter = (string) ($params['filter'] ?? self::DEFAULT_FILTER);
        if (!in_array($filter, self::ALLOWED_FILTERS, true)) {
            throw new \InvalidArgumentException("Unsupported participants filter: {$filter}");
        }

        $limit = max(1, min((int) ($params['limit'] ?? 100), 200));
        $offset = max(0, (int) ($params['offset'] ?? 0));

        return new self(
            chatUsername: $chatUsername,
            filter: $filter,
            limit: $limit,
            offset: $offset,
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
