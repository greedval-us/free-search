<?php

namespace Tests\Unit;

use App\Exceptions\Public\PublicValidationException;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;
use PHPUnit\Framework\TestCase;

class SiteIntelTargetGuardTest extends TestCase
{
    public function test_it_rejects_private_ip_targets(): void
    {
        $guard = $this->guardResolvingTo(['127.0.0.1']);

        $this->expectException(PublicValidationException::class);

        $guard->assertSafeUrl('http://127.0.0.1/');
    }

    public function test_it_allows_public_domain_targets(): void
    {
        $guard = $this->guardResolvingTo(['93.184.216.34']);

        $target = $guard->resolveSafeTarget('https://example.com/path');

        $this->assertSame('example.com', $target->host);
        $this->assertSame(443, $target->port);
        $this->assertSame('93.184.216.34', $target->ip);
        $this->assertSame('example.com:443:93.184.216.34', $target->curlResolveEntry());
    }

    public function test_it_rejects_hosts_without_dns_records(): void
    {
        $guard = $this->guardResolvingTo([]);

        $this->expectException(PublicValidationException::class);

        $guard->assertSafeUrl('https://missing.example/');
    }

    public function test_it_rejects_hosts_with_mixed_public_and_private_addresses(): void
    {
        $guard = $this->guardResolvingTo(['93.184.216.34', '10.0.0.1']);

        $this->expectException(PublicValidationException::class);

        $guard->assertSafeUrl('https://rebind.example/');
    }

    public function test_it_rejects_non_http_schemes(): void
    {
        $guard = $this->guardResolvingTo(['93.184.216.34']);

        $this->expectException(PublicValidationException::class);

        $guard->assertSafeUrl('file://example.com/etc/passwd');
    }

    public function test_it_rejects_trailing_dot_hosts_that_cannot_be_pinned_reliably(): void
    {
        $guard = $this->guardResolvingTo(['93.184.216.34']);

        $this->expectException(PublicValidationException::class);

        $guard->assertSafeUrl('https://example.com./');
    }

    /**
     * @param  list<string>  $addresses
     */
    private function guardResolvingTo(array $addresses): SiteIntelTargetGuard
    {
        $resolver = new class($addresses) implements SiteIntelHostResolverInterface
        {
            /**
             * @param  list<string>  $addresses
             */
            public function __construct(
                private readonly array $addresses,
            ) {}

            public function resolve(string $host): array
            {
                return $this->addresses;
            }
        };

        return new SiteIntelTargetGuard($resolver);
    }
}
