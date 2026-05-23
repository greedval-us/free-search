<?php

namespace App\Modules\SiteIntel\Providers;

use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteWhoisClientInterface;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthHttpInspectorInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthSslInspectorInterface;
use App\Modules\SiteIntel\Infrastructure\Clients\DomainLiteDnsResolver;
use App\Modules\SiteIntel\Infrastructure\Clients\DomainLiteWhoisClient;
use App\Modules\SiteIntel\Infrastructure\Clients\SeoAuditHttpFetcher;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthDnsResolver;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthHttpInspector;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthSslInspector;
use App\Support\Providers\BindingsServiceProvider;

final class SiteIntelServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            SiteHealthDnsResolverInterface::class => SiteHealthDnsResolver::class,
            SiteHealthHttpInspectorInterface::class => SiteHealthHttpInspector::class,
            SiteHealthSslInspectorInterface::class => SiteHealthSslInspector::class,
            SeoAuditHttpFetcherInterface::class => SeoAuditHttpFetcher::class,
            DomainLiteDnsResolverInterface::class => DomainLiteDnsResolver::class,
            DomainLiteWhoisClientInterface::class => DomainLiteWhoisClient::class,
        ];
    }
}
