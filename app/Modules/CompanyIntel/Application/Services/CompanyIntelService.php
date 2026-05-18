<?php

namespace App\Modules\CompanyIntel\Application\Services;

use App\Modules\CompanyIntel\Application\Services\CompanyIntel\CompanyDomainIntelAssembler;
use App\Modules\CompanyIntel\Application\Services\CompanyIntel\CompanyOsintLinkBuilder;
use App\Modules\CompanyIntel\Application\Services\CompanyIntel\CompanySummaryBuilder;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteWhoisLookup;

final class CompanyIntelService
{
    public function __construct(
        private readonly DomainLiteDnsResolverInterface $dnsResolver,
        private readonly DomainLiteWhoisLookup $whoisLookup,
        private readonly CompanyDomainIntelAssembler $domainIntelAssembler,
        private readonly CompanyOsintLinkBuilder $osintLinkBuilder,
        private readonly CompanySummaryBuilder $summaryBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $query, ?string $normalizedDomain): array
    {
        $domain = $normalizedDomain;
        $dns = null;
        $whois = null;

        if ($domain !== null) {
            $dns = $this->dnsResolver->resolve($domain);
            $whois = $this->whoisLookup->lookup($domain);
        }

        return [
            'query' => $query,
            'domain' => $domain,
            'checkedAt' => now()->toIso8601String(),
            'domainIntel' => $this->domainIntelAssembler->assemble($dns, $whois),
            'osintLinks' => $this->osintLinkBuilder->build($query, $domain),
            'summary' => $this->summaryBuilder->build($dns, $whois),
        ];
    }
}
