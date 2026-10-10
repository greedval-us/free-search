<?php

namespace App\Modules\NewsMediaIntel\Domain\DTO;

final readonly class NewsMarketingLookupDTO
{
    /** @param list<string> $competitors */
    public function __construct(
        public string $query,
        public NewsSearchOptionsDTO $options,
        public string $brand = '',
        public array $competitors = [],
        public string $domain = '',
    ) {}
}
