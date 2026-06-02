<?php

namespace App\Modules\Mastodon\DTO\Request;

final readonly class MastodonParserStartDTO
{
    public function __construct(
        public int $userId,
        public string $account,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toContext(): array
    {
        return [
            'account' => $this->account,
        ];
    }
}
