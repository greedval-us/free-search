<?php

namespace App\Modules\SiteIntel\Application\Services;

use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteDnsResolver;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteRiskScoreCalculator;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteWhoisLookup;
use Carbon\Carbon;

final class DomainLiteService
{
    public function __construct(
        private readonly DomainLiteDnsResolver $dnsResolver,
        private readonly DomainLiteWhoisLookup $whoisLookup,
        private readonly DomainLiteRiskScoreCalculator $riskScoreCalculator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $domain): array
    {
        $dns = $this->dnsResolver->resolve($domain);
        $whois = $this->whoisLookup->lookup($domain);
        $risk = $this->riskScoreCalculator->calculate($dns, $whois);

        return [
            'domain' => $domain,
            'checkedAt' => Carbon::now()->toIso8601String(),
            'dns' => $dns,
            'whois' => $whois,
            'risk' => $risk,
        ];
    }
}
