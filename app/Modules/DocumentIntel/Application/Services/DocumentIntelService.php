<?php

namespace App\Modules\DocumentIntel\Application\Services;

use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteDnsResolver;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteWhoisLookup;

final class DocumentIntelService
{
    public function __construct(
        private readonly DomainLiteDnsResolver $dnsResolver,
        private readonly DomainLiteWhoisLookup $whoisLookup,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $query, ?string $domain): array
    {
        $dns = null;
        $whois = null;

        if ($domain !== null) {
            $dns = $this->dnsResolver->resolve($domain);
            $whois = $this->whoisLookup->lookup($domain);
        }

        $docPivots = $this->buildDocumentPivots($query, $domain);
        $signals = $this->buildSignals($dns, $whois, $domain);

        return [
            'query' => $query,
            'domain' => $domain,
            'checkedAt' => now()->toIso8601String(),
            'signals' => $signals,
            'recommendations' => $this->buildRecommendations($signals),
            'documentPivots' => $docPivots,
            'domainIntel' => [
                'available' => $dns !== null && $whois !== null,
                'dns' => $dns === null ? null : [
                    'aCount' => count($dns['a'] ?? []),
                    'mxCount' => count($dns['mx'] ?? []),
                    'hasSpf' => (bool) ($dns['emailSecurity']['hasSpf'] ?? false),
                    'hasDmarc' => (bool) ($dns['emailSecurity']['hasDmarc'] ?? false),
                ],
                'whois' => $whois === null ? null : [
                    'available' => (bool) ($whois['available'] ?? false),
                    'registrar' => $whois['registrar'] ?? null,
                    'createdAt' => $whois['createdAt'] ?? null,
                    'expiresAt' => $whois['expiresAt'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function buildDocumentPivots(string $query, ?string $domain): array
    {
        $target = $domain ?? $query;
        $encodedTarget = rawurlencode($target);

        return [
            ['label' => 'pdf_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:pdf')],
            ['label' => 'docx_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:docx')],
            ['label' => 'xlsx_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:xlsx')],
            ['label' => 'pptx_search', 'url' => 'https://www.google.com/search?q=' . rawurlencode('site:' . $target . ' filetype:pptx')],
            ['label' => 'robots_txt', 'url' => 'https://' . $encodedTarget . '/robots.txt'],
            ['label' => 'sitemap_xml', 'url' => 'https://' . $encodedTarget . '/sitemap.xml'],
            ['label' => 'wayback_docs', 'url' => 'https://web.archive.org/web/*/' . $encodedTarget . '/*'],
            ['label' => 'github_leaks', 'url' => 'https://github.com/search?q=' . rawurlencode($target . ' password OR confidential OR internal')],
        ];
    }

    /**
     * @param array<string, mixed>|null $dns
     * @param array<string, mixed>|null $whois
     * @return array<int, string>
     */
    private function buildSignals(?array $dns, ?array $whois, ?string $domain): array
    {
        $signals = [];

        if ($domain === null) {
            $signals[] = 'domain_not_detected';

            return $signals;
        }

        if ($dns !== null && empty($dns['a']) && empty($dns['aaaa'])) {
            $signals[] = 'no_dns_resolution';
        }

        if ($dns !== null && ($dns['emailSecurity']['hasSpf'] ?? false) !== true) {
            $signals[] = 'missing_spf';
        }

        if ($dns !== null && ($dns['emailSecurity']['hasDmarc'] ?? false) !== true) {
            $signals[] = 'missing_dmarc';
        }

        if ($whois !== null && ($whois['available'] ?? false) !== true) {
            $signals[] = 'whois_unavailable';
        }

        if ($signals === []) {
            $signals[] = 'baseline_ok';
        }

        return $signals;
    }

    /**
     * @param array<int, string> $signals
     * @return array<int, string>
     */
    private function buildRecommendations(array $signals): array
    {
        $recommendations = [];

        if (in_array('domain_not_detected', $signals, true)) {
            $recommendations[] = 'specify_company_domain';
        }
        if (in_array('no_dns_resolution', $signals, true)) {
            $recommendations[] = 'validate_domain_and_dns';
        }
        if (in_array('missing_spf', $signals, true)) {
            $recommendations[] = 'configure_spf';
        }
        if (in_array('missing_dmarc', $signals, true)) {
            $recommendations[] = 'configure_dmarc';
        }
        if (in_array('whois_unavailable', $signals, true)) {
            $recommendations[] = 'check_whois_visibility';
        }
        if (in_array('baseline_ok', $signals, true)) {
            $recommendations[] = 'start_document_collection';
            $recommendations[] = 'review_document_metadata';
        }

        return array_values(array_unique($recommendations));
    }
}
