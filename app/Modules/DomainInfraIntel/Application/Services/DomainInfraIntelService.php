<?php

namespace App\Modules\DomainInfraIntel\Application\Services;

use App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel\AsnLookupClient;
use App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel\CertificateTransparencyClient;
use App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel\DomainIpResolver;
use App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel\DomainRdapClient;
use App\Modules\DomainInfraIntel\Application\Services\DomainInfraIntel\NeighborDomainResolver;
use App\Modules\DomainInfraIntel\Domain\DTO\DomainInfraIntelResultDTO;
use App\Modules\SiteIntel\Support\DomainNormalizer;

final class DomainInfraIntelService
{
    public function __construct(
        private readonly DomainIpResolver $ipResolver,
        private readonly DomainRdapClient $rdapClient,
        private readonly CertificateTransparencyClient $certificateTransparencyClient,
        private readonly AsnLookupClient $asnLookupClient,
        private readonly NeighborDomainResolver $neighborDomainResolver,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(string $domain): array
    {
        $normalized = DomainNormalizer::normalizeDomain($domain);
        if ($normalized === null) {
            return (new DomainInfraIntelResultDTO(
                domain: $domain,
                ips: [],
                rdap: [],
                crtsh: [],
                asn: [],
                neighbors: [],
                error: 'Invalid domain',
            ))->toArray();
        }

        $ips = $this->ipResolver->resolve($normalized);
        $asn = $this->asnLookupClient->lookup($ips[0] ?? null);

        return (new DomainInfraIntelResultDTO(
            domain: $normalized,
            ips: $ips,
            rdap: $this->rdapClient->lookup($normalized),
            crtsh: $this->certificateTransparencyClient->lookup($normalized),
            asn: $asn,
            neighbors: $this->neighborDomainResolver->resolve($ips),
        ))->toArray();
    }
}

