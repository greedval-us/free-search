<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Activity\RequestLogRunUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShifrTransportTest extends TestCase
{
    use RefreshDatabase;

    public static function operations(): array
    {
        return [
            'hash' => ['hash', ['text' => 'pilot', 'algorithm' => 'sha256', 'hmac_key' => 'test-key']],
            'transform' => ['transform', ['text' => 'pilot', 'operation' => 'base64_encode']],
            'ioc' => ['ioc-extract', ['text' => 'https://example.com']],
            'jwt' => ['jwt-inspect', ['token' => 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJwaWxvdCJ9.c2lnbmF0dXJl', 'secret' => 'test-key']],
            'classic' => ['classic', ['text' => 'pilot', 'cipher' => 'vigenere', 'direction' => 'encrypt', 'key' => 'KEY']],
        ];
    }

    #[DataProvider('operations')]
    public function test_operations_accept_post_body_and_reject_legacy_get_urls(string $path, array $payload): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/shifr/'.$path, $payload)
            ->assertOk()->assertJsonPath('ok', true)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/shifr/'.$path.'?'.http_build_query($payload))->assertStatus(405);
        $this->assertNull(app(RequestLogRunUrlBuilder::class)->build('/shifr/'.$path, 'GET', $payload));
    }

    public function test_shifr_operations_require_authentication(): void
    {
        $this->postJson('/shifr/hash', ['text' => 'pilot'])->assertUnauthorized();
    }

    public function test_shifr_rejects_post_without_csrf_token(): void
    {
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'local');

        $this->postJson('/shifr/hash', ['text' => 'pilot'])->assertStatus(419);
    }
}
