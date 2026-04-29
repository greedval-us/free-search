<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

use Carbon\Carbon;

final class DomainMailPostureService
{
    public function __construct(
        private readonly EmailDnsResolver $dnsResolver,
        private readonly EmailSpfParser $spfParser,
        private readonly EmailSpfIncludeResolver $spfIncludeResolver,
        private readonly EmailDmarcParser $dmarcParser,
        private readonly EmailDmarcReportAnalyzer $dmarcReportAnalyzer,
        private readonly EmailProviderDetector $providerDetector,
        private readonly EmailSecurityScoreCalculator $scoreCalculator,
        private readonly EmailDomainWebSnapshot $domainWebSnapshot,
        private readonly EmailDeliverabilityHintBuilder $deliverabilityHintBuilder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(string $domain): array
    {
        $domain = mb_strtolower(trim($domain));
        $dns = $this->dnsResolver->resolve($domain);
        $spf = $this->spfParser->parse($dns['txt']);
        $dmarc = $this->dmarcParser->parse($dns['dmarc']);
        $webSnapshot = $this->domainWebSnapshot->inspect($domain);

        return [
            'checkedAt' => Carbon::now()->toIso8601String(),
            'domain' => $domain,
            'dns' => $dns,
            'provider' => $this->providerDetector->detect($domain, $dns['mx']),
            'spf' => $spf,
            'spfExpandedIncludes' => $this->spfIncludeResolver->resolve($spf['includes']),
            'dmarc' => $dmarc,
            'dmarcReports' => $this->dmarcReportAnalyzer->analyze($domain, $dmarc),
            'scores' => $this->scoreCalculator->calculate($dns, $spf, $dmarc, [
                'isDisposable' => false,
                'isRoleAccount' => false,
            ]),
            'webSnapshot' => $webSnapshot,
            'deliverability' => $this->deliverabilityHintBuilder->build($dns, $spf, $dmarc, $webSnapshot),
        ];
    }
}
