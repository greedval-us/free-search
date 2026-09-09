<?php

namespace Tests\Feature;

use App\Exceptions\Public\PublicValidationException;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthHttpInspector;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthSslInspector;
use App\Modules\SiteIntel\Support\ResolvedSiteIntelTarget;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteHealthNetworkSafetyTest extends TestCase
{
    #[DataProvider('redirectLimits')]
    public function test_redirect_limit_never_returns_an_unvisited_target(int $limit): void
    {
        config()->set('osint.site_intel.http.max_redirects', $limit);
        Http::preventStrayRequests();
        $responses = [];
        for ($step = 0; $step <= $limit; $step++) {
            $responses['https://example.com/'.$step] = Http::response('', 302, [
                'Location' => $step === $limit ? 'https://127.0.0.1/' : 'https://example.com/'.($step + 1),
            ]);
        }
        Http::fake($responses);
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->with('example.com')->andReturn(['93.184.216.34']);
        $result = app(SiteHealthHttpInspector::class)->inspect('https://example.com/0');
        $this->assertSame('https://example.com/'.$limit, $result['finalUrl']);
        Http::assertSentCount($limit + 1);
    }

    public static function redirectLimits(): array
    {
        return [[0], [5]];
    }

    public function test_ssl_rejects_private_addresses_before_opening_a_socket(): void
    {
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->once()->with('example.com')->andReturn(['127.0.0.1']);
        $this->expectException(PublicValidationException::class);
        app(SiteHealthSslInspector::class)->inspect('https://example.com/');
    }

    public function test_ssl_socket_address_uses_pinned_ip_and_requested_port(): void
    {
        $target = new ResolvedSiteIntelTarget('https://example.com:8443/', 'example.com', 8443, '93.184.216.34');
        $this->assertSame('ssl://93.184.216.34:8443', $target->sslSocketAddress());
        $ipv6 = new ResolvedSiteIntelTarget('https://example.com/', 'example.com', 443, '2606:4700:4700::1111');
        $this->assertSame('ssl://[2606:4700:4700::1111]:443', $ipv6->sslSocketAddress());
    }
}
