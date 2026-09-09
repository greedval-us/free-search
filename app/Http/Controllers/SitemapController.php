<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        private readonly SitemapService $sitemapService,
    ) {}

    public function __invoke(): Response
    {
        return response(
            $this->sitemapService->toXml(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}
