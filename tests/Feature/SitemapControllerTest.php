<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    public function test_it_returns_an_xml_sitemap_for_public_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee(route('home', absolute: true), false);
        $response->assertSee(route('privacy', absolute: true), false);
        $response->assertSee(route('terms', absolute: true), false);
    }
}
