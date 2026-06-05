<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\NewsMediaIntel\Application\Contracts\NewsMediaIntelServiceInterface;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelLookupDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\NewsMediaIntelResultDTO;
use App\Modules\NewsMediaIntel\Domain\DTO\SentimentSummaryDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsMediaIntelControllerIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_media_intel_controller_returns_payload_without_fetching_feeds(): void
    {
        $user = User::factory()->create();

        $this->mock(NewsMediaIntelServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('monitor')
                ->once()
                ->withArgs(fn (NewsMediaIntelLookupDTO $lookup): bool => $lookup->query === 'acme')
                ->andReturn(new NewsMediaIntelResultDTO(
                    query: 'acme',
                    mentions: [],
                    topics: [],
                    timeline: [],
                    sentiment: new SentimentSummaryDTO(positive: 1, neutral: 2, negative: 0),
                ));
        });

        $this
            ->actingAs($user)
            ->getJson(route('news-media-intel.lookup', ['query' => ' acme ', 'locale' => 'en']))
            ->assertOk()
            ->assertJsonPath('data.query', 'acme')
            ->assertJsonPath('data.sentiment.positive', 1)
            ->assertJsonPath('data.sentiment.neutral', 2);
    }
}
