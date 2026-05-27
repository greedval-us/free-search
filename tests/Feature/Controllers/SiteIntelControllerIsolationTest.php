<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\Feature\Controllers\Concerns\MocksSiteIntelServices;
use Tests\TestCase;

class SiteIntelControllerIsolationTest extends TestCase
{
    use CreatesPaidUser;
    use MocksSiteIntelServices;
    use RefreshDatabase;

    public function test_site_health_controller_uses_service_and_returns_json_data(): void
    {
        $user = User::factory()->create();

        $this->mockSiteHealthCheck('https://example.com/', [
            'target' => 'https://example.com/',
            'status' => 'ok',
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('site-intel.site-health', ['target' => 'example.com']))
            ->assertOk()
            ->assertJsonPath('data.target', 'https://example.com/')
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_domain_lite_controller_uses_normalized_domain(): void
    {
        $user = User::factory()->create();

        $this->mockDomainLiteLookup('www.example.com', [
            'domain' => 'www.example.com',
            'dns' => ['a' => ['93.184.216.34']],
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('site-intel.domain-lite', ['domain' => 'https://www.example.com/path']))
            ->assertOk()
            ->assertJsonPath('data.domain', 'www.example.com')
            ->assertJsonPath('data.dns.a.0', '93.184.216.34');
    }

    public function test_site_intel_analytics_controller_isolated_from_external_clients(): void
    {
        $user = $this->paidUser();

        $this->mockSiteIntelAnalyze('https://example.com/', 'example.com', [
            'domain' => 'example.com',
            'score' => 88,
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('site-intel.analytics', ['target' => 'example.com']))
            ->assertOk()
            ->assertJsonPath('data.domain', 'example.com')
            ->assertJsonPath('data.score', 88);
    }

    public function test_seo_audit_controller_passes_normalized_input_to_service(): void
    {
        $user = $this->paidUser();

        $this->mockSeoAudit('https://example.com/', 12, 'content-site', [
            'target' => 'https://example.com/',
            'crawl' => ['limit' => 12],
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('site-intel.seo-audit', [
                'target' => 'example.com',
                'crawl_limit' => 12,
                'platform_type' => 'content-site',
            ]))
            ->assertOk()
            ->assertJsonPath('data.target', 'https://example.com/')
            ->assertJsonPath('data.crawl.limit', 12);
    }
}
