<?php

namespace App\Modules\Mastodon\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class MastodonStatusContextResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $ancestors
     * @param array<int, array<string, mixed>> $descendants
     * @param array<int, array<string, mixed>> $descendantsTree
     */
    public function __construct(
        public readonly array $ancestors,
        public readonly array $descendants,
        public readonly array $descendantsTree,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ancestors' => $this->ancestors,
            'descendants' => $this->descendants,
            'descendantsTree' => $this->descendantsTree,
        ];
    }
}
