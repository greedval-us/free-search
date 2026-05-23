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
use App\Support\Providers\BindingsServiceProvider;

final class DomainInfraIntelServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            DomainIpResolverInterface::class => DomainIpResolver::class,
            DomainRdapClientInterface::class => DomainRdapClient::class,
            CertificateTransparencyClientInterface::class => CertificateTransparencyClient::class,
            AsnLookupClientInterface::class => AsnLookupClient::class,
            NeighborDomainResolverInterface::class => NeighborDomainResolver::class,
        ];
    }
}
