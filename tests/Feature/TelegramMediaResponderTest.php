<?php

namespace Tests\Feature;

use App\Http\Responses\TelegramMediaResponder;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class TelegramMediaResponderTest extends TestCase
{
    private array $downloadPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->downloadPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_it_normalizes_untrusted_download_metadata(): void
    {
        $downloadPath = null;
        $gateway = Mockery::mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('downloadMediaToFile')
            ->once()
            ->andReturnUsing(function (array $media, string $path) use (&$downloadPath): string {
                $downloadPath = $path;
                file_put_contents($path, 'media');

                return $path;
            });

        try {
            $response = (new TelegramMediaResponder($gateway))->respond([
                'media' => ['_' => 'messageMediaDocument'],
                'download' => [
                    'name' => "unsafe/\r\nname",
                    'ext' => '../../php',
                    'mime' => "text/html\r\nx-injected: true",
                ],
            ]);

            $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
            $this->assertSame('attachment; filename=unsafe-name', $response->headers->get('Content-Disposition'));
            $this->assertNotNull($downloadPath);
            $this->assertStringNotContainsString('..', $downloadPath);
        } finally {
            if (is_string($downloadPath)) {
                @unlink($downloadPath);
            }
        }
    }

    #[DataProvider('activeDocuments')]
    public function test_active_and_unknown_documents_download_without_browser_execution(string $content, string $name, string $mime): void
    {
        config()->set('security.headers.enabled', true);

        $response = $this->mediaResponse($content, $name, $mime);

        $response->assertOk()
            ->assertDownload($name)
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame("sandbox; default-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public static function activeDocuments(): array
    {
        return [
            'HTML' => ['<!DOCTYPE html><script>alert(document.cookie)</script>', 'document.html', 'text/html'],
            'SVG' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.cookie)"></svg>', 'document.svg', 'image/svg+xml'],
            'JavaScript' => ['alert(document.cookie)', 'document.js', 'application/javascript'],
            'XHTML' => ['<?xml version="1.0"?><html xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script></html>', 'document.xhtml', 'application/xhtml+xml'],
            'unknown' => ['unrecognized file', 'document.bin', 'application/octet-stream'],
            'HTML advertised as image' => ['<!DOCTYPE html><script>alert(1)</script>', 'photo.jpg', 'image/jpeg'],
            'SVG advertised as image' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>', 'photo.png', 'image/png'],
            'HTML advertised as audio' => ['<!DOCTYPE html><script>alert(1)</script>', 'sound.mp3', 'audio/mpeg'],
        ];
    }

    #[DataProvider('safeMedia')]
    public function test_detected_safe_media_preserves_inline_preview_without_trusting_metadata(string $content, string $name, string $mime): void
    {
        config()->set('security.headers.enabled', false);

        $response = $this->mediaResponse($content, $name, 'text/html');

        $response->assertOk()
            ->assertHeader('Content-Type', $mime)
            ->assertHeader('Content-Disposition', 'inline; filename='.$name)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
    }

    public static function safeMedia(): array
    {
        return [
            'PNG' => [base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aZz8AAAAASUVORK5CYII='), 'photo.png', 'image/png'],
            'WAV' => ['RIFF'.pack('V', 40).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('Vv*', 4, 0, 0), 'sound.wav', 'audio/x-wav'],
            'MP4' => [pack('N', 24).'ftypisom'.pack('N', 0).'isommp42'.pack('N', 8).'mdat', 'video.mp4', 'video/mp4'],
        ];
    }

    public function test_download_filename_preserves_unicode_and_escapes_quotes(): void
    {
        $response = $this->mediaResponse('<html>document</html>', 'отчёт "2026".html', 'text/html');

        $response->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertStringContainsString("filename*=utf-8''", $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString(rawurlencode('отчёт "2026".html'), $response->headers->get('Content-Disposition'));
    }

    private function mediaResponse(string $content, string $name, string $mime): TestResponse
    {
        $gateway = $this->mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('downloadMediaToFile')->once()->andReturnUsing(function (array $media, string $path) use ($content): string {
            $this->downloadPaths[] = $path;
            file_put_contents($path, $content);

            return $path;
        });
        Route::middleware('web')->get('/_document-media-test', fn () => app(TelegramMediaResponder::class)->respond([
            'media' => ['_' => 'messageMediaDocument'],
            'download' => ['name' => $name, 'mime' => $mime],
        ]));

        return $this->get('/_document-media-test');
    }

    public function test_it_removes_temporary_file_when_download_fails(): void
    {
        $downloadPath = null;
        $gateway = Mockery::mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('downloadMediaToFile')
            ->once()
            ->andReturnUsing(function (array $media, string $path) use (&$downloadPath): never {
                $downloadPath = $path;

                throw new RuntimeException('Telegram download failed.');
            });

        try {
            (new TelegramMediaResponder($gateway))->respond([
                'media' => ['_' => 'messageMediaDocument'],
                'download' => ['ext' => '.jpg'],
            ]);

            $this->fail('The Telegram download exception must be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Telegram download failed.', $exception->getMessage());
            $this->assertNotNull($downloadPath);
            $this->assertFileDoesNotExist($downloadPath);
        }
    }
}
