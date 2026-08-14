<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\SiteIntel\Support\ResolvedSiteIntelTarget;

final class SiteIntelHttpRequestOptions
{
    /**
     * @return array<string|int, mixed>
     */
    public function build(ResolvedSiteIntelTarget $target, bool $verifySsl): array
    {
        $options = [
            'allow_redirects' => false,
            'verify' => $verifySsl,
        ];

        $resolveEntry = $target->curlResolveEntry();
        if ($resolveEntry === null) {
            return $options;
        }

        if (! defined('CURLOPT_RESOLVE')) {
            throw new ExternalServiceUnavailableException(
                'errors.api.service_unavailable',
                'site_intel_secure_transport_unavailable',
            );
        }

        $options['curl'] = [
            constant('CURLOPT_RESOLVE') => [$resolveEntry],
        ];

        return $options;
    }
}
