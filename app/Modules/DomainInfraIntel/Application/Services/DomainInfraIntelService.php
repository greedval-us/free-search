<?php

namespace App\Modules\DomainInfraIntel\Application\Services;

use App\Modules\DomainInfraIntel\Application\Contracts\AsnLookupClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\CertificateTransparencyClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainIpResolverInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainRdapClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainInfraIntelServiceInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\NeighborDomainResolverInterface;
use App\Modules\DomainInfraIntel\Domain\DTO\DomainInfraIntelResultDTO;
use App\Modules\SiteIntel\Support\DomainNormalizer;

final class DomainInfraIntelService implements DomainInfraIntelServiceInterface
{
    public function __construct(
        private readonly DomainIpResolverInterface $ipResolver,
        private readonly DomainRdapClientInterface $rdapClient,
        private readonly CertificateTransparencyClientInterface $certificateTransparencyClient,
        private readonly AsnLookupClientInterface $asnLookupClient,
        private readonly NeighborDomainResolverInterface $neighborDomainResolver,
    ) {
    }

    public function inspect(string $domain): DomainInfraIntelResultDTO
    {
        $normalized = DomainNormalizer::normalizeDomain($domain);
        if ($normalized === null) {
            return new DomainInfraIntelResultDTO(
                domain: $domain,
                ips: [],
                rdap: [],
                crtsh: [],
                asn: [],
                neighbors: [],
                error: 'Invalid domain',
            );
        }

        $ips = $this->ipResolver->resolve($normalized);
        $asn = $this->asnLookupClient->lookup($ips[0] ?? null);

        return new DomainInfraIntelResultDTO(
            domain: $normalized,
            ips: $ips,
            rdap: $this->rdapClient->lookup($normalized),
            crtsh: $this->certificateTransparencyClient->lookup($normalized),
            asn: $asn,
            neighbors: $this->neighborDomainResolver->resolve($ips),
        );
    }
}
