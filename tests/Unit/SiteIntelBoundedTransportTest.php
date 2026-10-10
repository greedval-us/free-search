<?php

namespace Tests\Unit;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\SiteIntel\Infrastructure\Clients\SiteIntelBoundedTransport;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

class SiteIntelBoundedTransportTest extends TestCase
{
    #[DataProvider('lengthHeaders')]
    public function test_transport_stops_before_consuming_the_whole_stream_regardless_of_length_header(array $headers): void
    {
        $remaining = 1024;
        $source = new PumpStream(static function (int $length) use (&$remaining): string|false {
            if ($remaining === 0) {
                return false;
            }
            $chunk = str_repeat('x', min($length, $remaining));
            $remaining -= strlen($chunk);

            return $chunk;
        });
        $sink = null;
        $handler = static function ($request, array $options) use ($source, $headers, &$sink) {
            $sink = $options['sink'];
            while (! $source->eof()) {
                $sink->write($source->read(4));
            }

            return Create::promiseFor(new Response(200, $headers, $sink));
        };

        try {
            (new SiteIntelBoundedTransport)->wrap($handler, 8)(new Request('GET', 'https://example.com'), [])->wait();
            $this->fail('The transport must abort when the decoded body exceeds the limit.');
        } catch (ExternalServiceUnavailableException $exception) {
            $this->assertSame('site_intel_response_too_large', $exception->errorCode());
        }

        $this->assertSame(12, $source->tell());
        $this->assertSame(1012, $remaining);
        $this->assertInstanceOf(StreamInterface::class, $sink);
        $this->assertFalse($sink->isWritable(), 'The failed response sink must be closed.');
        $source->close();
    }

    public static function lengthHeaders(): array
    {
        return [
            'missing length' => [[]],
            'lying small length' => [['Content-Length' => '1']],
            'lying large length' => [['Content-Length' => '999999']],
            'chunked' => [['Transfer-Encoding' => 'chunked']],
        ];
    }

    #[DataProvider('acceptedBodies')]
    public function test_transport_accepts_the_complete_body_below_and_at_the_limit(string $body): void
    {
        $handler = static function ($request, array $options) use ($body) {
            foreach (str_split($body, 4) as $chunk) {
                $options['sink']->write($chunk);
            }
            $options['sink']->rewind();

            return Create::promiseFor(new Response(200, [], $options['sink']));
        };

        $response = (new SiteIntelBoundedTransport)->wrap($handler, 8)(new Request('GET', 'https://example.com'), [])->wait();

        $this->assertSame($body, (string) $response->getBody());
        $response->getBody()->close();
    }

    public static function acceptedBodies(): array
    {
        return [['1234567'], ['12345678']];
    }

    #[DataProvider('failureModes')]
    public function test_transport_closes_its_sink_for_synchronous_and_promise_failures(bool $synchronous): void
    {
        $failure = new RuntimeException('controlled transport failure');
        $sink = null;
        $handler = static function ($request, array $options) use ($failure, $synchronous, &$sink) {
            $sink = $options['sink'];
            $sink->write('1234');
            if ($synchronous) {
                throw $failure;
            }

            return Create::rejectionFor($failure);
        };

        try {
            (new SiteIntelBoundedTransport)->wrap($handler, 8)(new Request('GET', 'https://example.com'), [])->wait();
            $this->fail('The original transport failure must be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertFalse($sink->isWritable());
    }

    public static function failureModes(): array
    {
        return [[true], [false]];
    }

    #[DataProvider('gzipBodies')]
    public function test_native_transport_enforces_the_limit_after_gzip_decompression(string $transport, int $decodedBytes, bool $accepted): void
    {
        $server = new Process([PHP_BINARY, dirname(__DIR__).'/Fixtures/site-intel-http-server.php', (string) $decodedBytes]);
        $server->setTimeout(10);
        $address = null;

        try {
            $server->start();
            $server->waitUntil(static function ($type, string $output) use (&$address): bool {
                if (preg_match('/LISTEN (127\.0\.0\.1:\d+)/', $output, $matches) !== 1) {
                    return false;
                }
                $address = $matches[1];

                return true;
            });
            $this->assertNotNull($address, $server->getErrorOutput());
            // Test native transports locally; production TargetGuard is untouched.
            $handler = (new SiteIntelBoundedTransport)->wrap(new $transport, 8);

            try {
                $response = $handler(new Request('GET', 'http://'.$address.'/'), [
                    'timeout' => 3,
                    'connect_timeout' => 1,
                    'proxy' => '',
                ])->wait();
                $this->assertTrue($accepted, 'A compressed response must be bounded by its decoded size.');
                $this->assertSame('xxxxxxxx', (string) $response->getBody());
                $response->getBody()->close();
            } catch (ExternalServiceUnavailableException $exception) {
                $this->assertFalse($accepted);
                $this->assertSame('site_intel_response_too_large', $exception->errorCode());
            }
        } finally {
            $server->stop(0);
        }
    }

    public static function gzipBodies(): array
    {
        return [
            'cURL exact limit' => [CurlHandler::class, 8, true],
            'cURL compressed expansion' => [CurlHandler::class, 65536, false],
            'PHP stream exact limit' => [StreamHandler::class, 8, true],
            'PHP stream compressed expansion' => [StreamHandler::class, 65536, false],
        ];
    }
}
