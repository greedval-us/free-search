<?php

namespace App\Modules\Mastodon\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class MastodonAccountStatusesResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $statuses
     */
    public function __construct(
        public readonly array $statuses,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'statuses' => $this->statuses,
        ];
    }
}
