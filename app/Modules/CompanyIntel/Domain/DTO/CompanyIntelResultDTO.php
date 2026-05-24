<?php

namespace App\Modules\CompanyIntel\Domain\DTO;

use App\Support\Contracts\ArrayPayloadable;

final class CompanyIntelResultDTO implements ArrayPayloadable
{
    /**
     * @param array<string, mixed> $domainIntel
     * @param array<int, array{label: string, url: string}> $osintLinks
     * @param array<string, mixed> $summary
     */
    public function __construct(
        public readonly string $query,
        public readonly ?string $domain,
        public readonly string $checkedAt,
        public readonly array $domainIntel,
        public readonly array $osintLinks,
        public readonly array $summary,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'domain' => $this->domain,
            'checkedAt' => $this->checkedAt,
            'domainIntel' => $this->domainIntel,
            'osintLinks' => $this->osintLinks,
            'summary' => $this->summary,
        ];
    }
}
