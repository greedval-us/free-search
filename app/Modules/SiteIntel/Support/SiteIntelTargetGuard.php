<?php

namespace App\Modules\SiteIntel\Support;

use App\Exceptions\Public\PublicValidationException;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;

final class SiteIntelTargetGuard
{
    public function __construct(
        private readonly SiteIntelHostResolverInterface $hostResolver,
    ) {}

    public function assertSafeUrl(string $url): void
    {
        $this->resolveSafeTarget($url);
    }

    public function resolveSafeTarget(string $url): ResolvedSiteIntelTarget
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            throw $this->invalidTarget();
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $rawHost = (string) ($parts['host'] ?? '');
        $host = strtolower($rawHost);

        if (! in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || str_ends_with($rawHost, '.')
        ) {
            throw $this->invalidTarget();
        }

        $addresses = $this->hostResolver->resolve($host);
        if ($addresses === []) {
            throw $this->invalidTarget();
        }

        foreach ($addresses as $ip) {
            if ($this->isUnsafeIp($ip)) {
                throw $this->invalidTarget();
            }
        }

        return new ResolvedSiteIntelTarget(
            url: $url,
            host: $host,
            port: isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80),
            ip: $addresses[0],
        );
    }

    private function isUnsafeIp(string $ip): bool
    {
        if (filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false) {
            return false;
        }

        return true;
    }

    private function invalidTarget(): PublicValidationException
    {
        return new PublicValidationException(
            'errors.api.site_intel.invalid_target',
            'site_intel_invalid_target'
        );
    }
}
