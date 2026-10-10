<?php

namespace Tests\Feature;

use App\Modules\ParserSupport\ParserRunSourceRequestBudget;
use App\Modules\YouTube\Support\YouTubeApiConfig;
use App\Modules\YouTube\YouTubeDataApiClient;
use App\Support\Observability\ExternalServiceLogger;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationCredentialTransportTest extends TestCase
{
    public function test_youtube_sends_api_key_only_in_header(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://www.googleapis.com/youtube/v3/search*' => Http::response(['items' => []])]);
        $client = new YouTubeDataApiClient(YouTubeApiConfig::fromArray(['key' => 'test-youtube-key']), app(ExternalServiceLogger::class), app(ParserRunSourceRequestBudget::class));

        $this->assertSame(['items' => []], $client->search(['q' => 'pilot']));

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-Goog-Api-Key', 'test-youtube-key')
            && ! str_contains($request->url(), 'test-youtube-key') && ! isset($request['key']) && $request['q'] === 'pilot');
        Http::assertSentCount(1);
    }
}
