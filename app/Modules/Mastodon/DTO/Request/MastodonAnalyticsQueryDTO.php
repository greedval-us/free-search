<?php

namespace App\Modules\Mastodon\DTO\Request;

final readonly class MastodonAnalyticsQueryDTO
{
    public function __construct(
        public string $mode,
        public string $target,
        public int $limit,
        public int $pages,
        public string $dateFrom = '',
        public string $dateTo = '',
        public bool $resolve = true,
    ) {
    }
}
