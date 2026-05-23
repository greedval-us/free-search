<?php

namespace App\Modules\SiteIntel\Providers;

use App\Modules\SiteIntel\Application\Contracts\DomainLiteDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\DomainLiteWhoisClientInterface;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditHttpFetcherInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthHttpInspectorInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthSslInspectorInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;
use App\Modules\SiteIntel\Application\Services\DomainLiteService;
use App\Modules\SiteIntel\Application\Services\SeoAuditService;
use App\Modules\SiteIntel\Application\Services\SiteHealthService;
use App\Modules\SiteIntel\Application\Services\SiteIntelAnalyticsService;
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
            SiteHealthServiceInterface::class => SiteHealthService::class,
            DomainLiteServiceInterface::class => DomainLiteService::class,
            SiteIntelAnalyticsServiceInterface::class => SiteIntelAnalyticsService::class,
            SeoAuditServiceInterface::class => SeoAuditService::class,
            SiteHealthDnsResolverInterface::class => SiteHealthDnsResolver::class,
            SiteHealthHttpInspectorInterface::class => SiteHealthHttpInspector::class,
            SiteHealthSslInspectorInterface::class => SiteHealthSslInspector::class,
            SeoAuditHttpFetcherInterface::class => SeoAuditHttpFetcher::class,
            DomainLiteDnsResolverInterface::class => DomainLiteDnsResolver::class,
            DomainLiteWhoisClientInterface::class => DomainLiteWhoisClient::class,
        ];
    }
}
