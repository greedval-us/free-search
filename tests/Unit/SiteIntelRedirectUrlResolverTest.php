<?php

namespace Tests\Unit;

use App\Modules\SiteIntel\Infrastructure\Clients\SiteIntelRedirectUrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SiteIntelRedirectUrlResolverTest extends TestCase
{
    #[DataProvider('redirects')]
    public function test_it_resolves_redirects(string $currentUrl, string $location, string $expected): void
    {
        $this->assertSame(
            $expected,
            (new SiteIntelRedirectUrlResolver)->resolve($currentUrl, $location),
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function redirects(): array
    {
        return [
            'absolute' => [
                'https://example.com/base/page',
                'https://other.example/result',
                'https://other.example/result',
            ],
            'protocol relative' => [
                'https://example.com/base/page',
                '//cdn.example/asset',
                'https://cdn.example/asset',
            ],
            'root relative' => [
                'https://example.com/base/page',
                '/result',
                'https://example.com/result',
            ],
            'path relative' => [
                'https://example.com/base/page',
                '../result',
                'https://example.com/result',
            ],
            'query relative' => [
                'https://example.com/base/page?old=1',
                '?new=2',
                'https://example.com/base/page?new=2',
            ],
        ];
    }
}
