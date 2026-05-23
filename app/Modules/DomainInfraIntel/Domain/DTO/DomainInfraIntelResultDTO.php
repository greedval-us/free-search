<?php

namespace App\Modules\DomainInfraIntel\Domain\DTO;

use App\Support\Contracts\ArrayPayloadable;

final class DomainInfraIntelResultDTO implements ArrayPayloadable
{
    /**
     * @param array<int, string> $ips
     * @param array<string, mixed> $rdap
     * @param array<int, array<string, mixed>> $crtsh
     * @param array<string, mixed> $asn
     * @param array<int, string> $neighbors
     */
    public function __construct(
        public readonly string $domain,
        public readonly array $ips,
        public readonly array $rdap,
        public readonly array $crtsh,
        public readonly array $asn,
        public readonly array $neighbors,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'ips' => $this->ips,
            'rdap' => $this->rdap,
            'crtsh' => $this->crtsh,
            'asn' => $this->asn,
            'neighbors' => $this->neighbors,
            'error' => $this->error,
        ];
    }
}
