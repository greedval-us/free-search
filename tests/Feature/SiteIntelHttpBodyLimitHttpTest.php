<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthDnsResolverInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteHealthSslInspectorInterface;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteIntelHttpBodyLimitHttpTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('locales')]
    public function test_oversized_response_returns_localized_public_503_without_partial_result(string $locale, string $message): void
    {
        config()->set('osint.site_intel.http.max_response_bytes', 8);
        $user = User::factory()->create();
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->with('example.com')->andReturn(['93.184.216.34']);
        $this->mock(SiteHealthDnsResolverInterface::class)->shouldReceive('resolve')->with('example.com')->andReturn(['a' => ['93.184.216.34'], 'aaaa' => []]);
        $this->mock(SiteHealthSslInspectorInterface::class)->shouldNotReceive('inspect');
        Http::preventStrayRequests();
        Http::fake(['https://example.com/' => Http::response('123456789', 200)]);

        $this->actingAs($user)
            ->getJson(route('site-intel.site-health', ['target' => 'example.com', 'locale' => $locale]))
            ->assertServiceUnavailable()
            ->assertExactJson([
                'ok' => false,
                'message' => $message,
                'code' => 'site_intel_response_too_large',
            ]);

        Http::assertSentCount(1);
    }

    public static function locales(): array
    {
        return [
            ['en', 'The site response exceeds the supported size. The analysis could not be completed.'],
            ['ru', 'Ответ сайта превышает допустимый размер. Не удалось завершить анализ.'],
        ];
    }
}
