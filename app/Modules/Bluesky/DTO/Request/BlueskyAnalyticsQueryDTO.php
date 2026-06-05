<?php

namespace App\Modules\Bluesky\DTO\Request;

final readonly class BlueskyAnalyticsQueryDTO
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
