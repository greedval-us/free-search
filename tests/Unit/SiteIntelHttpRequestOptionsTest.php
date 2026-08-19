<?php

namespace Tests\Unit;

use App\Modules\SiteIntel\Infrastructure\Clients\SiteIntelHttpRequestOptions;
use App\Modules\SiteIntel\Support\ResolvedSiteIntelTarget;
use PHPUnit\Framework\TestCase;

class SiteIntelHttpRequestOptionsTest extends TestCase
{
    public function test_it_pins_domain_to_prevalidated_ip(): void
    {
        $this->assertTrue(defined('CURLOPT_RESOLVE'));

        $target = new ResolvedSiteIntelTarget(
            url: 'https://example.com/path',
            host: 'example.com',
            port: 443,
            ip: '93.184.216.34',
        );

        $options = (new SiteIntelHttpRequestOptions)->build($target, true);

        $this->assertFalse($options['allow_redirects']);
        $this->assertTrue($options['verify']);
        $this->assertSame(
            ['example.com:443:93.184.216.34'],
            $options['curl'][constant('CURLOPT_RESOLVE')],
        );
    }
}
