<?php

namespace App\Modules\DocumentIntel\Application\Services;

use App\Modules\DocumentIntel\Application\Contracts\DocumentMetadataExtractorInterface;
use App\Modules\DocumentIntel\Application\Contracts\DocumentIntelServiceInterface;
use App\Modules\DocumentIntel\Application\Contracts\DocumentUrlCollectorInterface;
use App\Modules\DocumentIntel\Application\Services\DocumentIntel\DocumentPivotBuilder;
use App\Modules\DocumentIntel\Application\Services\DocumentIntel\DocumentRecommendationBuilder;
use App\Modules\DocumentIntel\Application\Services\DocumentIntel\DocumentRiskScorer;
use App\Modules\DocumentIntel\Application\Services\DocumentIntel\DocumentSignalBuilder;
use App\Modules\DocumentIntel\Application\Services\DocumentIntel\DocumentSummaryBuilder;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;
use App\Modules\SiteIntel\Application\Services\DomainLite\DomainLiteWhoisLookup;

final class DocumentIntelService implements DocumentIntelServiceInterface
{
    public function __construct(
        private readonly DomainLiteDnsResolverInterface $dnsResolver,
        private readonly DomainLiteWhoisLookup $whoisLookup,
        private readonly DocumentUrlCollectorInterface $documentUrlCollector,
        private readonly DocumentMetadataExtractorInterface $documentMetadataExtractor,
        private readonly DocumentPivotBuilder $pivotBuilder,
        private readonly DocumentSignalBuilder $signalBuilder,
        private readonly DocumentRecommendationBuilder $recommendationBuilder,
        private readonly DocumentRiskScorer $riskScorer,
        private readonly DocumentSummaryBuilder $summaryBuilder,
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

        $docPivots = $this->pivotBuilder->build($query, $domain);
        $signals = $this->signalBuilder->build($dns, $whois, $domain);
        $discoveredDocuments = $domain === null ? [] : $this->discoverDocuments($domain);
        $documentsRisk = $this->riskScorer->aggregate($discoveredDocuments);

        return [
            'query' => $query,
            'domain' => $domain,
            'checkedAt' => now()->toIso8601String(),
            'signals' => $signals,
            'recommendations' => $this->recommendationBuilder->build($signals),
            'documentPivots' => $docPivots,
            'documents' => $discoveredDocuments,
            'documentsSummary' => $this->summaryBuilder->build($discoveredDocuments, $documentsRisk),
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
     * @return array<int, array<string, mixed>>
     */
    private function discoverDocuments(string $domain): array
    {
        $urls = $this->documentUrlCollector->collect($domain);
        $documents = [];

        foreach ($urls as $url) {
            $document = $this->documentMetadataExtractor->extract($url);
            $document['risk'] = $this->riskScorer->score($document);
            $documents[] = $document;
        }

        return $documents;
    }
}
