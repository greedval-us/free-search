<?php

namespace App\Modules\SiteIntel\Application\Services;

use App\Modules\SiteIntel\Application\Contracts\SiteHealthServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthHttpInspectorInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthSslInspectorInterface;
use App\Modules\SiteIntel\DTO\Result\SiteHealthResultDTO;
use App\Modules\SiteIntel\Application\Services\SiteHealth\SiteHealthScoreCalculator;
use App\Modules\SiteIntel\Application\Services\SiteHealth\SiteHealthSecurityHeadersExtractor;
use Carbon\Carbon;
use RuntimeException;

final class SiteHealthService implements SiteHealthServiceInterface
{
    public function __construct(
        private readonly SiteHealthDnsResolverInterface $dnsResolver,
        private readonly SiteHealthHttpInspectorInterface $httpInspector,
        private readonly SiteHealthSslInspectorInterface $sslInspector,
        private readonly SiteHealthSecurityHeadersExtractor $securityHeadersExtractor,
        private readonly SiteHealthScoreCalculator $scoreCalculator,
    ) {
    }

    public function check(string $url): SiteHealthResultDTO
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            throw new RuntimeException('Invalid target host.');
        }

        $dns = $this->dnsResolver->resolve($host);
        $http = $this->httpInspector->inspect($url);
        $finalHeaders = $http['finalHeaders'];
        $ssl = $this->sslInspector->inspect(
            (string) parse_url((string) $http['finalUrl'], PHP_URL_HOST),
            str_starts_with((string) $http['finalUrl'], 'https://')
        );
        $securityHeaders = $this->securityHeadersExtractor->extract(is_array($finalHeaders) ? $finalHeaders : []);
        $score = $this->scoreCalculator->calculate($dns, $http, $ssl, $securityHeaders);

        return new SiteHealthResultDTO([
            'target' => [
                'input' => $url,
                'host' => $host,
            ],
            'checkedAt' => Carbon::now()->toIso8601String(),
            'dns' => $dns,
            'http' => [
                'chain' => $http['chain'],
                'finalUrl' => $http['finalUrl'],
                'finalStatus' => $http['finalStatus'],
                'totalRedirects' => $http['totalRedirects'],
            ],
            'headers' => $securityHeaders,
            'ssl' => $ssl,
            'score' => $score,
        ]);
    }
}
