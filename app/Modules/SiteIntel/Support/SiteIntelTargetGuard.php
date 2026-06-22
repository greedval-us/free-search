<?php

namespace App\Modules\SiteIntel\Support;

use App\Exceptions\Public\PublicValidationException;

final class SiteIntelTargetGuard
{
    public function assertSafeUrl(string $url): void
    {
        $host = $this->extractHost($url);
        if ($host === null) {
            throw $this->invalidTarget();
        }

        foreach ($this->resolveHostAddresses($host) as $ip) {
            if ($this->isUnsafeIp($ip)) {
                throw $this->invalidTarget();
            }
        }
    }

    private function extractHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    /**
     * @return list<string>
     */
    private function resolveHostAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = [];
        $ipv4 = gethostbynamel($host) ?: [];
        foreach ($ipv4 as $ip) {
            if (is_string($ip) && $ip !== '') {
                $addresses[] = $ip;
            }
        }

        if (defined('DNS_AAAA')) {
            $ipv6Records = @dns_get_record($host, DNS_AAAA);

            if (is_array($ipv6Records)) {
                foreach ($ipv6Records as $record) {
                    $ip = $record['ipv6'] ?? null;
                    if (is_string($ip) && $ip !== '') {
                        $addresses[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($addresses));
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
