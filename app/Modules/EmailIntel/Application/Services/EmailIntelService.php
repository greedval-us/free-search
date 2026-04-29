<?php

namespace App\Modules\EmailIntel\Application\Services;

use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailDomainIntelAssembler;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailEntityGraphBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailLocalPartAnalyzer;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailProfileClassifier;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailRecommendationBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailRiskBreakdownBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSearchLinkBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSignalBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSimilarDomainGenerator;
use App\Modules\EmailIntel\Domain\DTO\EmailIntelResultDTO;

final class EmailIntelService
{
    public function __construct(
        private readonly EmailDomainIntelAssembler $domainIntelAssembler,
        private readonly EmailLocalPartAnalyzer $localPartAnalyzer,
        private readonly EmailProfileClassifier $profileClassifier,
        private readonly EmailSignalBuilder $signalBuilder,
        private readonly EmailRiskBreakdownBuilder $riskBreakdownBuilder,
        private readonly EmailSearchLinkBuilder $searchLinkBuilder,
        private readonly EmailEntityGraphBuilder $graphBuilder,
        private readonly EmailRecommendationBuilder $recommendationBuilder,
        private readonly EmailSimilarDomainGenerator $similarDomainGenerator,
    ) {
    }

    public function lookup(string $email): EmailIntelResultDTO
    {
        [$localPart, $domain] = explode('@', $email, 2);

        $localPartAnalysis = $this->localPartAnalyzer->analyze($localPart);
        $profile = $this->profileClassifier->classify($email, $domain, $localPartAnalysis);
        $domainIntel = $this->domainIntelAssembler->assemble($domain, $profile);
        $signals = $this->signalBuilder->build($domainIntel, $profile, $localPartAnalysis);
        $riskBreakdown = $this->riskBreakdownBuilder->build($signals);
        $riskScore = $riskBreakdown['total'];
        $target = [
            'email' => $email,
            'localPart' => $localPart,
            'domain' => $domain,
            'normalized' => $email,
            'sha256' => hash('sha256', $email),
        ];
        $dnsPayload = $this->domainIntelAssembler->dnsPayload($domainIntel);
        $analytics = [
            'provider' => $domainIntel['provider'],
            'spf' => $domainIntel['spf'],
            'spfExpandedIncludes' => $domainIntel['spfExpandedIncludes'],
            'dmarc' => $domainIntel['dmarc'],
            'dmarcReports' => $domainIntel['dmarcReports'],
            'localPart' => $localPartAnalysis,
            'scores' => $domainIntel['scores'],
            'riskBreakdown' => $riskBreakdown,
            'deliverability' => $domainIntel['deliverability'],
            'searchLinks' => $this->searchLinkBuilder->build($email, $localPart, $domain),
            'similarDomains' => $this->similarDomainGenerator->generate($domain),
            'webSnapshot' => $domainIntel['webSnapshot'],
            'pivots' => [
                'username' => '/username',
                'siteIntel' => '/site-intel',
                'gravatar' => 'https://www.gravatar.com/avatar/' . md5($email) . '?d=404',
            ],
        ];
        $analytics['recommendations'] = $this->recommendationBuilder->build($signals, $analytics);
        $analytics['graph'] = $this->graphBuilder->build($target, $dnsPayload, $profile, $analytics, $signals);

        return new EmailIntelResultDTO(
            checkedAt: (string) $domainIntel['checkedAt'],
            target: $target,
            dns: $dnsPayload,
            profile: $profile,
            analytics: $analytics,
            riskScore: $riskScore,
            riskLevel: $this->riskLevel($riskScore),
            signals: $signals,
        );
    }

    private function riskLevel(int $riskScore): string
    {
        return match (true) {
            $riskScore >= 60 => 'high',
            $riskScore >= 30 => 'medium',
            $riskScore > 0 => 'low',
            default => 'clean',
        };
    }
}
