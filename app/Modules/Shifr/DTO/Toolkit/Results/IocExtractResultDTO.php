<?php

namespace App\Modules\Shifr\DTO\Toolkit\Results;

use App\Modules\Shifr\DTO\Contracts\ShifrResultDataInterface;

final class IocExtractResultDTO implements ShifrResultDataInterface
{
    /**
     * @param array<string, int> $counts
     * @param array<string, array<int, string>> $items
     */
    public function __construct(
        public readonly array $counts,
        public readonly array $items,
    ) {
    }

    public function toArray(): array
    {
        return [
            'counts' => $this->counts,
            'items' => $this->items,
        ];
    }
}

