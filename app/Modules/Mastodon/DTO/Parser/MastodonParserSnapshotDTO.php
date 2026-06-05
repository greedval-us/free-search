<?php

namespace App\Modules\Mastodon\DTO\Parser;

use App\Support\Contracts\ArrayPayloadable;

final readonly class MastodonParserSnapshotDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed>|null $resolvedAccount
     * @param array<int, array<string, mixed>> $statusesIndex
     * @param array<int, array<string, mixed>> $commentsIndex
     */
    public function __construct(
        private string $account,
        private ?array $resolvedAccount,
        private array $statusesIndex,
        private array $commentsIndex,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account' => $this->account,
            'resolvedAccount' => $this->resolvedAccount,
            'statusesCount' => count($this->statusesIndex),
            'commentsCount' => count($this->commentsIndex),
            'statusesIndex' => $this->statusesIndex,
            'commentsIndex' => $this->commentsIndex,
        ];
    }
}
