<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_secure_web_response_contains_security_headers_and_nonce_based_csp(): void
    {
        Config::set('security.headers.enabled', true);
        Config::set('security.hsts.enabled', true);
        Config::set('security.content_security_policy.enabled', true);

        Route::middleware('web')
            ->get('/_security-headers-test', static fn () => response('ok'));

        $response = $this->get('https://localhost/_security-headers-test');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Content-Security-Policy');

        $this->assertMatchesRegularExpression(
            "/script-src 'self' 'nonce-[^']+'/",
            (string) $response->headers->get('Content-Security-Policy')
        );
    }

    public function test_inline_scripts_and_styles_use_the_csp_nonce(): void
    {
        Config::set('security.headers.enabled', true);
        Config::set('security.content_security_policy.enabled', true);

        $response = $this->get('https://localhost/');
        $policy = (string) $response->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $policy, $matches);
        preg_match_all(
            '/<(?:script|style)\b[^>]*>/i',
            $response->getContent(),
            $executableTags
        );

        $response->assertOk();
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertNotEmpty($executableTags[0]);

        foreach ($executableTags[0] as $tag) {
            if (str_contains($tag, 'type="application/json"')) {
                continue;
            }

            $this->assertStringContainsString(
                'nonce="'.($matches[1] ?? '').'"',
                $tag
            );
        }
    }

    public function test_csp_can_be_excluded_for_third_party_admin_pages(): void
    {
        Config::set('security.headers.enabled', true);
        Config::set('security.content_security_policy.enabled', true);
        Config::set('security.content_security_policy.excluded_paths', ['back-office/*']);

        Route::middleware('web')
            ->get('/back-office/dashboard', static fn () => response('ok'));

        $response = $this->get('/back-office/dashboard');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeaderMissing('Content-Security-Policy');
    }
}
