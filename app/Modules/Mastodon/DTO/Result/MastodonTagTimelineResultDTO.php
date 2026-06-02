<?php

namespace App\Modules\Mastodon\DTO\Result;

use App\Support\Contracts\ArrayPayloadable;

final class MastodonTagTimelineResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, array<string, mixed>> $statuses
     * @param array<string, mixed> $analytics
     * @param array<string, mixed> $pagination
     */
    public function __construct(
        public readonly array $statuses,
        public readonly array $analytics,
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
            'analytics' => $this->analytics,
            'pagination' => $this->pagination,
        ];
    }
}
