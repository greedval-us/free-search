<?php

namespace Tests\Unit;

use App\Exceptions\Public\PublicValidationException;
use App\Modules\SiteIntel\Support\SiteIntelTargetGuard;
use PHPUnit\Framework\TestCase;

class SiteIntelTargetGuardTest extends TestCase
{
    public function test_it_rejects_private_ip_targets(): void
    {
        $guard = new SiteIntelTargetGuard();

        $this->expectException(PublicValidationException::class);

        $guard->assertSafeUrl('http://127.0.0.1/');
    }

    public function test_it_allows_public_domain_targets(): void
    {
        $guard = new SiteIntelTargetGuard();

        $guard->assertSafeUrl('http://93.184.216.34/');

        $this->assertTrue(true);
    }
}
