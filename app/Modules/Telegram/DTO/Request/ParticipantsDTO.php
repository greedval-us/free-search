<?php

namespace App\Modules\Telegram\DTO\Request;

/**
 * Backward-compatible alias for SearchParticipantsDTO.
 *
 * @deprecated Use SearchParticipantsDTO instead.
 */
class ParticipantsDTO extends SearchParticipantsDTO
{
    public function __construct(
        string $chatUsername,
        string $filter = 'channelParticipantsRecent',
        int $limit = 200,
        int $offset = 0,
        array $hash = []
    ) {
        parent::__construct(
            chatUsername: $chatUsername,
            filter: $filter,
            limit: $limit,
            offset: $offset,
            hash: $hash
        );
    }
}
