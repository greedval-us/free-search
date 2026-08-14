<?php

namespace App\Modules\SiteIntel\Infrastructure\Clients;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Throwable;

final class SiteIntelRedirectUrlResolver
{
    public function resolve(string $currentUrl, string $location): ?string
    {
        try {
            $resolved = UriResolver::resolve(
                new Uri($currentUrl),
                new Uri($location),
            );
        } catch (Throwable) {
            return null;
        }

        $url = (string) $resolved;

        return $url !== '' ? $url : null;
    }
}
