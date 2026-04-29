<?php

namespace App\Modules\EmailIntel\Application\Services;

use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailDmarcParser;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailDnsResolver;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailDomainWebSnapshot;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailEntityGraphBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailLocalPartAnalyzer;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailProviderDetector;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailRecommendationBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSearchLinkBuilder;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSecurityScoreCalculator;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSimilarDomainGenerator;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailSpfParser;
use App\Modules\EmailIntel\Domain\DTO\EmailIntelResultDTO;
use Carbon\Carbon;

final class EmailIntelService
{
    /**
     * @var array<int, string>
     */
    private const FREE_PROVIDERS = [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'icloud.com',
        'proton.me',
        'protonmail.com',
        'mail.ru',
        'yandex.ru',
        'ya.ru',
        'rambler.ru',
    ];

    /**
     * This is intentionally small and local. Expand it as config later if you want.
     *
     * @var array<int, string>
     */
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com',
        'guerrillamail.com',
        'mailinator.com',
        'tempmail.com',
        'temp-mail.org',
        'yopmail.com',
        'trashmail.com',
    ];

    public function __construct(
        private readonly EmailDnsResolver $dnsResolver,
        private readonly EmailProviderDetector $providerDetector,
        private readonly EmailSpfParser $spfParser,
        private readonly EmailDmarcParser $dmarcParser,
        private readonly EmailLocalPartAnalyzer $localPartAnalyzer,
        private readonly EmailSecurityScoreCalculator $scoreCalculator,
        private readonly EmailSearchLinkBuilder $searchLinkBuilder,
        private readonly EmailEntityGraphBuilder $graphBuilder,
        private readonly EmailRecommendationBuilder $recommendationBuilder,
        private readonly EmailSimilarDomainGenerator $similarDomainGenerator,
        private readonly EmailDomainWebSnapshot $domainWebSnapshot,
    ) {
    }

    public function lookup(string $email): EmailIntelResultDTO
    {
        [$localPart, $domain] = explode('@', $email, 2);

        $dns = $this->dnsResolver->resolve($domain);
        $spf = $this->spfParser->parse($dns['txt']);
        $dmarc = $this->dmarcParser->parse($dns['dmarc']);
        $localPartAnalysis = $this->localPartAnalyzer->analyze($localPart);
        $provider = $this->providerDetector->detect($domain, $dns['mx']);

        $isDisposable = in_array($domain, self::DISPOSABLE_DOMAINS, true);
        $isFreeProvider = in_array($domain, self::FREE_PROVIDERS, true);
        $isRoleAccount = (bool) $localPartAnalysis['isRoleAccount'];

        $signals = $this->signals(
            hasMx: count($dns['mx']) > 0,
            spfStrictness: (string) $spf['strictness'],
            dmarcStrength: (string) $dmarc['strength'],
            isDisposable: $isDisposable,
            isFreeProvider: $isFreeProvider,
            isRoleAccount: $isRoleAccount,
            looksRandom: (bool) $localPartAnalysis['looksRandom'],
            hasDomainAddress: count($dns['a']) + count($dns['aaaa']) > 0,
        );
        $riskScore = $this->riskScore($signals);
        $target = [
            'email' => $email,
            'localPart' => $localPart,
            'domain' => $domain,
            'normalized' => $email,
            'sha256' => hash('sha256', $email),
        ];
        $profile = [
            'isFreeProvider' => $isFreeProvider,
            'isDisposable' => $isDisposable,
            'isRoleAccount' => $isRoleAccount,
            'gravatarHash' => md5($email),
            'gravatarUrl' => 'https://www.gravatar.com/avatar/' . md5($email) . '?d=404',
        ];
        $dnsPayload = [
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
        $analytics = [
            'provider' => $provider,
            'spf' => $spf,
            'dmarc' => $dmarc,
            'localPart' => $localPartAnalysis,
            'scores' => $this->scoreCalculator->calculate($dns, $spf, $dmarc, $profile),
            'searchLinks' => $this->searchLinkBuilder->build($email, $localPart, $domain),
            'similarDomains' => $this->similarDomainGenerator->generate($domain),
            'webSnapshot' => $this->domainWebSnapshot->inspect($domain),
            'pivots' => [
                'username' => '/username',
                'siteIntel' => '/site-intel',
                'gravatar' => 'https://www.gravatar.com/avatar/' . md5($email) . '?d=404',
            ],
        ];
        $analytics['recommendations'] = $this->recommendationBuilder->build($signals, $analytics);
        $analytics['graph'] = $this->graphBuilder->build($target, $dnsPayload, $profile, $analytics, $signals);

        return new EmailIntelResultDTO(
            checkedAt: Carbon::now()->toIso8601String(),
            target: $target,
            dns: $dnsPayload,
            profile: $profile,
            analytics: $analytics,
            riskScore: $riskScore,
            riskLevel: $this->riskLevel($riskScore),
            signals: $signals,
        );
    }

    /**
     * @return array<int, array{type: string, level: string, message: string}>
     */
    private function signals(
        bool $hasMx,
        string $spfStrictness,
        string $dmarcStrength,
        bool $isDisposable,
        bool $isFreeProvider,
        bool $isRoleAccount,
        bool $looksRandom,
        bool $hasDomainAddress,
    ): array {
        $signals = [];

        if (! $hasMx) {
            $signals[] = ['type' => 'missing_mx', 'level' => 'high', 'message' => 'Domain has no MX records.'];
        }

        if (! $hasDomainAddress) {
            $signals[] = ['type' => 'domain_not_resolving', 'level' => 'medium', 'message' => 'Domain has no A/AAAA records.'];
        }

        if ($spfStrictness === 'missing') {
            $signals[] = ['type' => 'missing_spf', 'level' => 'medium', 'message' => 'SPF record is missing.'];
        } elseif ($spfStrictness === 'open') {
            $signals[] = ['type' => 'open_spf', 'level' => 'high', 'message' => 'SPF policy allows all senders.'];
        } elseif (in_array($spfStrictness, ['soft', 'neutral', 'unknown'], true)) {
            $signals[] = ['type' => 'weak_spf', 'level' => 'low', 'message' => 'SPF policy is present but not strict.'];
        }

        if ($dmarcStrength === 'missing') {
            $signals[] = ['type' => 'missing_dmarc', 'level' => 'medium', 'message' => 'DMARC record is missing.'];
        } elseif ($dmarcStrength === 'monitoring') {
            $signals[] = ['type' => 'monitoring_dmarc', 'level' => 'low', 'message' => 'DMARC is in monitoring mode only.'];
        }

        if ($isDisposable) {
            $signals[] = ['type' => 'disposable_provider', 'level' => 'high', 'message' => 'Domain matches a local disposable-mail list.'];
        }

        if ($isRoleAccount) {
            $signals[] = ['type' => 'role_account', 'level' => 'low', 'message' => 'Local part looks like a role/shared inbox.'];
        }

        if ($looksRandom) {
            $signals[] = ['type' => 'random_local_part', 'level' => 'low', 'message' => 'Local part looks random or machine-generated.'];
        }

        if ($isFreeProvider) {
            $signals[] = ['type' => 'free_provider', 'level' => 'info', 'message' => 'Domain is a common free mailbox provider.'];
        }

        if ($signals === []) {
            $signals[] = ['type' => 'baseline_ok', 'level' => 'positive', 'message' => 'No strong technical risk signals detected.'];
        }

        return $signals;
    }

    /**
     * @param array<int, array{type: string, level: string, message: string}> $signals
     */
    private function riskScore(array $signals): int
    {
        $score = 0;

        foreach ($signals as $signal) {
            $score += match ($signal['level']) {
                'high' => 30,
                'medium' => 15,
                'low' => 7,
                default => 0,
            };
        }

        return min(100, $score);
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
