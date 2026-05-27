<?php

namespace App\Providers;

use App\Services\Access\AccessPolicyResolver;
use App\Services\Access\Contracts\AccessPolicyResolverInterface;
use App\Services\Access\Contracts\FeatureAccessRequestResolverInterface;
use App\Services\Access\Contracts\FeatureAccessServiceInterface;
use App\Services\Access\Contracts\FeatureUsageCounterInterface;
use App\Services\Access\Contracts\PlanQuotaResolverInterface;
use App\Services\Access\FeatureAccessRequestResolver;
use App\Services\Access\FeatureAccessService;
use App\Services\Access\FeatureUsageCounter;
use App\Services\Access\PlanQuotaResolver;
use App\Support\Providers\BindingsServiceProvider;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use App\Support\Reports\ReportFilenamePolicy;
use App\Support\Reports\ReportsConfig;

final class SupportServiceProvider extends BindingsServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            ReportsConfig::class,
            static fn (): ReportsConfig => ReportsConfig::fromArray(
                (array) config('osint.reports', []),
                (string) config('app.timezone', 'UTC')
            )
        );
    }

    protected function bindings(): array
    {
        return [
            AccessPolicyResolverInterface::class => AccessPolicyResolver::class,
            FeatureAccessRequestResolverInterface::class => FeatureAccessRequestResolver::class,
            FeatureAccessServiceInterface::class => FeatureAccessService::class,
            FeatureUsageCounterInterface::class => FeatureUsageCounter::class,
            PlanQuotaResolverInterface::class => PlanQuotaResolver::class,
            ReportFilenamePolicyInterface::class => ReportFilenamePolicy::class,
        ];
    }
}
