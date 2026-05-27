<?php

namespace Tests\Feature\Controllers\Concerns;

use App\Modules\SiteIntel\Application\Contracts\DomainLiteServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SeoAuditServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthServiceInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelAnalyticsServiceInterface;

trait MocksSiteIntelServices
{
    private function mockSiteHealthCheck(string $url, array $result): void
    {
        $this->mock(SiteHealthServiceInterface::class, function ($mock) use ($url, $result): void {
            $mock->shouldReceive('check')
                ->once()
                ->with($url)
                ->andReturn($result);
        });
    }

    private function mockDomainLiteLookup(string $domain, array $result): void
    {
        $this->mock(DomainLiteServiceInterface::class, function ($mock) use ($domain, $result): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->with($domain)
                ->andReturn($result);
        });
    }

    private function mockSiteIntelAnalyze(string $url, string $domain, array $result): void
    {
        $this->mock(SiteIntelAnalyticsServiceInterface::class, function ($mock) use ($url, $domain, $result): void {
            $mock->shouldReceive('analyze')
                ->once()
                ->with($url, $domain)
                ->andReturn($result);
        });
    }

    private function mockSeoAudit(string $url, int $limit, ?string $platformType, array $result): void
    {
        $this->mock(SeoAuditServiceInterface::class, function ($mock) use ($url, $limit, $platformType, $result): void {
            $mock->shouldReceive('audit')
                ->once()
                ->with($url, $limit, $platformType)
                ->andReturn($result);
        });
    }
}
