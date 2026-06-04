<?php

namespace App\Modules\SiteIntel\Application\Services;

use App\Modules\SiteIntel\Application\Contracts\DomainLiteServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteRiskScoreCalculator;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteWhoisLookup;
use App\Modules\SiteIntel\DTO\Result\DomainLiteResultDTO;
use Carbon\Carbon;

final class DomainLiteService implements DomainLiteServiceInterface
{
    public function __construct(
        private readonly DomainLiteDnsResolverInterface $dnsResolver,
        private readonly DomainLiteWhoisLookup $whoisLookup,
        private readonly DomainLiteRiskScoreCalculator $riskScoreCalculator,
    ) {
    }

    public function lookup(string $domain): DomainLiteResultDTO
    {
        $dns = $this->dnsResolver->resolve($domain);
        $whois = $this->whoisLookup->lookup($domain);
        $whois = $this->enrichWhois($whois);
        $risk = $this->riskScoreCalculator->calculate($dns, $whois);

        return new DomainLiteResultDTO([
            'domain' => $domain,
            'checkedAt' => Carbon::now()->toIso8601String(),
            'dns' => $dns,
            'whois' => $whois,
            'risk' => $risk,
        ]);
    }

    /**
     * @param array<string, mixed> $whois
     * @return array<string, mixed>
     */
    private function enrichWhois(array $whois): array
    {
        $now = Carbon::now();
        $domainAgeDays = null;
        $daysToExpiry = null;

        if (is_string($whois['createdAt'] ?? null) && $whois['createdAt'] !== '') {
            $domainAgeDays = (int) floor(Carbon::parse($whois['createdAt'])->diffInDays($now, false));
        }

        if (is_string($whois['expiresAt'] ?? null) && $whois['expiresAt'] !== '') {
            $daysToExpiry = (int) floor($now->diffInDays(Carbon::parse($whois['expiresAt']), false));
        }

        $whois['timing'] = [
            'domainAgeDays' => $domainAgeDays,
            'daysToExpiry' => $daysToExpiry,
        ];

        return $whois;
    }
}
