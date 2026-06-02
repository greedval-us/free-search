<?php

namespace App\Modules\Mastodon\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class MastodonAccountFollowersResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $accounts
     */
    public function __construct(
        public readonly array $accounts,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'accounts' => $this->accounts,
        ];
    }
}
