<?php

namespace App\Modules\DomainInfraIntel\Providers;

use App\Modules\DomainInfraIntel\Application\Contracts\AsnLookupClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\CertificateTransparencyClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainIpResolverInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\DomainRdapClientInterface;
use App\Modules\DomainInfraIntel\Application\Contracts\NeighborDomainResolverInterface;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\AsnLookupClient;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\CertificateTransparencyClient;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\DomainIpResolver;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\DomainRdapClient;
use App\Modules\DomainInfraIntel\Infrastructure\Clients\NeighborDomainResolver;
use Illuminate\Support\ServiceProvider;

final class DomainInfraIntelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DomainIpResolverInterface::class, DomainIpResolver::class);
        $this->app->bind(DomainRdapClientInterface::class, DomainRdapClient::class);
        $this->app->bind(CertificateTransparencyClientInterface::class, CertificateTransparencyClient::class);
        $this->app->bind(AsnLookupClientInterface::class, AsnLookupClient::class);
        $this->app->bind(NeighborDomainResolverInterface::class, NeighborDomainResolver::class);
    }
}

