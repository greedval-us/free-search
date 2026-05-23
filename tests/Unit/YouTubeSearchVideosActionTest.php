<?php

namespace Tests\Unit;

use App\Modules\YouTube\Actions\Request\SearchVideosAction;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;
use App\Modules\YouTube\Presenters\YouTubeSearchItemPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeChannelInputNormalizer;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use App\Modules\YouTube\Support\YouTubeDurationFormatter;
use App\Modules\YouTube\Support\YouTubeUrlBuilder;
use Tests\TestCase;

class YouTubeSearchVideosActionTest extends TestCase
{
    public function test_channel_filter_handle_is_resolved_before_search_request(): void
    {
        $gateway = new FakeYouTubeSearchGateway();
        $action = new SearchVideosAction(
            $gateway,
            new YouTubeSearchItemPresenter(new YouTubeUrlBuilder()),
            new YouTubeVideoPresenter(new YouTubeDurationFormatter(), new YouTubeUrlBuilder()),
            new YouTubeChannelResolver($gateway, new YouTubeChannelInputNormalizer()),
        );

        $action->handle(new YouTubeSearchQueryDTO(
            query: 'osint',
            type: 'video',
            maxResults: 10,
            order: 'relevance',
            safeSearch: 'moderate',
            channelId: '@yoj996',
        ));

        $this->assertSame('@yoj996', $gateway->channelLookupCalls[0]['forHandle']);
        $this->assertSame(FakeYouTubeSearchGateway::CHANNEL_ID, $gateway->searchCalls[0]['channelId']);
    }
}

class FakeYouTubeSearchGateway implements YouTubeGatewayInterface
{
    public const CHANNEL_ID = 'UCresolved12345678901234';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $searchCalls = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $channelLookupCalls = [];

    public function search(array $params): array
    {
        $this->searchCalls[] = $params;

        return ['items' => []];
    }

    public function videos(array $params): array
    {
        return ['items' => []];
    }

    public function channels(array $params): array
    {
        $this->channelLookupCalls[] = $params;

        return ['items' => [['id' => self::CHANNEL_ID]]];
    }

    public function commentThreads(array $params): array
    {
        return ['items' => []];
    }
}
