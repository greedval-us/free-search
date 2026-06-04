<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final class NewsMediaIntelLookupDTO
{
    public function __construct(
        public readonly string $query,
    ) {
    }
}
