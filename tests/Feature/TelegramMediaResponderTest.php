<?php

namespace Tests\Feature;

use App\Http\Responses\TelegramMediaResponder;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TelegramMediaResponderTest extends TestCase
{
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
            $this->assertSame('inline; filename="unsafe-name"', $response->headers->get('Content-Disposition'));
            $this->assertNotNull($downloadPath);
            $this->assertStringNotContainsString('..', $downloadPath);
        } finally {
            if (is_string($downloadPath)) {
                @unlink($downloadPath);
            }
        }
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
