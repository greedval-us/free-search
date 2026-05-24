<?php

namespace App\Modules\EmailIntel\Providers;

use App\Modules\EmailIntel\Application\Contracts\DomainMailPostureServiceInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailBulkIntelServiceInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailDnsResolverInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailDomainWebSnapshotInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailIntelServiceInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailTxtRecordLookupInterface;
use App\Modules\EmailIntel\Application\Services\EmailIntel\DomainMailPostureService;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailBulkIntelService;
use App\Modules\EmailIntel\Application\Services\EmailIntelService;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailDnsResolver;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailDomainWebSnapshot;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailTxtRecordLookup;
use App\Support\Providers\BindingsServiceProvider;

final class EmailIntelServiceProvider extends BindingsServiceProvider
{
    protected function bindings(): array
    {
        return [
            EmailDnsResolverInterface::class => EmailDnsResolver::class,
            EmailDomainWebSnapshotInterface::class => EmailDomainWebSnapshot::class,
            EmailTxtRecordLookupInterface::class => EmailTxtRecordLookup::class,
            EmailIntelServiceInterface::class => EmailIntelService::class,
            EmailBulkIntelServiceInterface::class => EmailBulkIntelService::class,
            DomainMailPostureServiceInterface::class => DomainMailPostureService::class,
        ];
    }
}
