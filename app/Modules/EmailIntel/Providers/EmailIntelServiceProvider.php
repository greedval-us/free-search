<?php

namespace App\Modules\EmailIntel\Providers;

use App\Modules\EmailIntel\Application\Contracts\EmailDnsResolverInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailDomainWebSnapshotInterface;
use App\Modules\EmailIntel\Application\Contracts\EmailTxtRecordLookupInterface;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailDnsResolver;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailDomainWebSnapshot;
use App\Modules\EmailIntel\Infrastructure\Clients\EmailTxtRecordLookup;
use Illuminate\Support\ServiceProvider;

final class EmailIntelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmailDnsResolverInterface::class, EmailDnsResolver::class);
        $this->app->bind(EmailDomainWebSnapshotInterface::class, EmailDomainWebSnapshot::class);
        $this->app->bind(EmailTxtRecordLookupInterface::class, EmailTxtRecordLookup::class);
    }
}

