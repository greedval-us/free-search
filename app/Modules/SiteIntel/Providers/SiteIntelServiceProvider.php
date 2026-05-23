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
use Illuminate\Support\ServiceProvider;

final class SiteIntelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SiteHealthDnsResolverInterface::class, SiteHealthDnsResolver::class);
        $this->app->bind(SiteHealthHttpInspectorInterface::class, SiteHealthHttpInspector::class);
        $this->app->bind(SiteHealthSslInspectorInterface::class, SiteHealthSslInspector::class);
        $this->app->bind(SeoAuditHttpFetcherInterface::class, SeoAuditHttpFetcher::class);
        $this->app->bind(DomainLiteDnsResolverInterface::class, DomainLiteDnsResolver::class);
        $this->app->bind(DomainLiteWhoisClientInterface::class, DomainLiteWhoisClient::class);
    }
}

