<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Exceptions\Public\PublicValidationException;
use App\Modules\SiteIntel\Application\Contracts\SiteIntelHostResolverInterface;
use App\Modules\SiteIntel\Infrastructure\Clients\SeoAuditHttpFetcher;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteHealthHttpInspector;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\PumpStream;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteIntelHttpBodyLimitTest extends TestCase
{
    #[DataProvider('clients')]
    public function test_oversized_response_fails_instead_of_returning_success(string $client, string $method): void
    {
        config()->set('osint.site_intel.http.max_response_bytes', 8);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/' => Http::response('123456789', 200, ['Content-Length' => '1'])]);
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->with('example.com')->andReturn(['93.184.216.34']);

        try {
            app($client)->{$method}('https://example.com/');
            $this->fail('Oversized responses must not be accepted as complete results.');
        } catch (ExternalServiceUnavailableException $exception) {
            $this->assertSame('site_intel_response_too_large', $exception->errorCode());
        }

        Http::assertSentCount(1);
    }

    public static function clients(): array
    {
        return [[SeoAuditHttpFetcher::class, 'fetch'], [SiteHealthHttpInspector::class, 'inspect']];
    }

    #[DataProvider('acceptedBodies')]
    public function test_seo_keeps_complete_bodies_below_and_at_the_limit(string $body): void
    {
        config()->set('osint.site_intel.http.max_response_bytes', 8);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/' => Http::response($body, 200)]);
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->with('example.com')->andReturn(['93.184.216.34']);

        $result = app(SeoAuditHttpFetcher::class)->fetch('https://example.com/');

        $this->assertSame($body, $result['body']);
        $this->assertNull($result['error']);
        Http::assertSentCount(1);
    }

    public static function acceptedBodies(): array
    {
        return [['1234567'], ['12345678']];
    }

    public function test_alternate_response_stream_is_read_only_to_limit_plus_one_and_closed(): void
    {
        config()->set('osint.site_intel.http.max_response_bytes', 8);
        $read = 0;
        $closed = false;
        $source = new PumpStream(static function (int $length) use (&$read): string {
            $read += $length;

            return str_repeat('x', $length);
        });
        $stream = FnStream::decorate($source, ['close' => static function () use ($source, &$closed): void {
            $closed = true;
            $source->close();
        }]);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/' => Http::response($stream, 200)]);
        $this->mock(SiteIntelHostResolverInterface::class)->shouldReceive('resolve')->with('example.com')->andReturn(['93.184.216.34']);

        try {
            app(SeoAuditHttpFetcher::class)->fetch('https://example.com/');
            $this->fail('An alternate infinite stream must also be bounded.');
        } catch (ExternalServiceUnavailableException $exception) {
            $this->assertSame('site_intel_response_too_large', $exception->errorCode());
        }

        $this->assertSame(9, $read);
        $this->assertTrue($closed);
        Http::assertSentCount(1);
    }

    #[DataProvider('clients')]
    public function test_each_redirect_response_has_its_own_budget_and_pinned_target(string $client, string $method): void
    {
        config()->set('osint.site_intel.http.max_response_bytes', 8);
        config()->set('osint.site_intel.http.timeout_seconds', 7);
        config()->set('osint.site_intel.http.verify_ssl', true);
        $this->mock(SiteIntelHostResolverInterface::class)
            ->shouldReceive('resolve')->once()->with('example.com')->andReturn(['93.184.216.34'])
            ->shouldReceive('resolve')->once()->with('other.example')->andReturn(['1.1.1.1']);
        $optionsSeen = [];
        Http::preventStrayRequests();
        Http::fake(static function ($request, array $options) use (&$optionsSeen) {
            $optionsSeen[] = $options;

            return match ($request->url()) {
                'https://example.com/' => Http::response('12345678', 302, ['Location' => 'https://other.example/']),
                'https://other.example/' => Http::response('abcdefgh', 200),
                default => null,
            };
        });

        $result = app($client)->{$method}('https://example.com/');

        $this->assertSame(200, $result['status'] ?? $result['finalStatus']);
        $this->assertSame(['example.com:443:93.184.216.34'], $optionsSeen[0]['curl'][constant('CURLOPT_RESOLVE')]);
        $this->assertSame(['other.example:443:1.1.1.1'], $optionsSeen[1]['curl'][constant('CURLOPT_RESOLVE')]);
        foreach ($optionsSeen as $options) {
            $this->assertFalse($options['allow_redirects']);
            $this->assertTrue($options['verify']);
            $this->assertSame(7, $options['timeout']);
            $this->assertSame(10, $options['connect_timeout']);
        }
        Http::assertSentCount(2);
    }

    #[DataProvider('clients')]
    public function test_redirect_targets_are_revalidated_before_another_request(string $client, string $method): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://example.com/' => Http::response('', 302, ['Location' => 'http://internal.example/'])]);
        $this->mock(SiteIntelHostResolverInterface::class)
            ->shouldReceive('resolve')->once()->with('example.com')->andReturn(['93.184.216.34'])
            ->shouldReceive('resolve')->once()->with('internal.example')->andReturn(['127.0.0.1']);

        try {
            app($client)->{$method}('https://example.com/');
            $this->fail('Unsafe redirects must be rejected before an outbound request.');
        } catch (PublicValidationException $exception) {
            $this->assertSame('site_intel_invalid_target', $exception->errorCode());
        }

        Http::assertSentCount(1);
    }
}
