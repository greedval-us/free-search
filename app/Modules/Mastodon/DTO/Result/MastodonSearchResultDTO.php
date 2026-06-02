<?php

namespace App\Modules\Mastodon\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class MastodonSearchResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $statuses
     * @param array<int, array<string, mixed>> $accounts
     * @param array<int, array<string, mixed>> $hashtags
     * @param array{query: string, type: string, limit: int, offset: int, nextOffset: int|null, hasMore: bool} $pagination
     */
    public function __construct(
        public readonly array $statuses,
        public readonly array $accounts,
        public readonly array $hashtags,
        public readonly array $pagination,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'statuses' => $this->statuses,
            'accounts' => $this->accounts,
            'hashtags' => $this->hashtags,
            'pagination' => $this->pagination,
        ];
    }
}
