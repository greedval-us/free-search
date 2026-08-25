<?php

namespace Tests\Feature;

use Tests\TestCase;

class RequestLocaleTest extends TestCase
{
    public function test_locale_cookie_controls_server_rendered_language(): void
    {
        $response = $this->withUnencryptedCookie('locale', 'ru')->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="ru"', false);
        $response->assertSee('/social-preview.png', false);
        $response->assertDontSee('Uraboros | Intelligence Workspace - Uraboros', false);
        $response->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('locale', 'ru')
        );
    }

    public function test_invalid_locale_cookie_uses_fallback_locale(): void
    {
        config()->set('app.fallback_locale', 'en');

        $response = $this->withUnencryptedCookie('locale', 'invalid')->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="en"', false);
    }
}
