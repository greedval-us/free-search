<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

use Carbon\Carbon;

final class EmailDomainIntelAssembler
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
     * @param array<string, mixed> $profile
     * @return array<string, mixed>
     */
    public function assemble(string $domain, array $profile = []): array
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
            'scores' => $this->scoreCalculator->calculate($dns, $spf, $dmarc, $profile + [
                'isDisposable' => false,
                'isRoleAccount' => false,
            ]),
            'webSnapshot' => $webSnapshot,
            'deliverability' => $this->deliverabilityHintBuilder->build($dns, $spf, $dmarc, $webSnapshot),
        ];
    }

    /**
     * @param array<string, mixed> $domainIntel
     * @return array<string, mixed>
     */
    public function dnsPayload(array $domainIntel): array
    {
        $dns = $domainIntel['dns'];
        $spf = $domainIntel['spf'];
        $dmarc = $domainIntel['dmarc'];

        return [
            'mx' => $dns['mx'],
            'a' => $dns['a'],
            'aaaa' => $dns['aaaa'],
            'txt' => $dns['txt'],
            'dmarc' => $dns['dmarc'],
            'emailSecurity' => [
                'hasSpf' => (bool) $spf['present'],
                'hasDmarc' => (bool) $dmarc['present'],
            ],
        ];
    }
}
