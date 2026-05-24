<?php

namespace Tests\Unit;

use App\Modules\YouTube\Actions\Request\AnalyticsSummaryAction;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsReportBuilder;
use App\Modules\YouTube\Core\Contracts\YouTubeGatewayInterface;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\Presenters\YouTubeChannelPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeChannelInputNormalizer;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use App\Modules\YouTube\Support\YouTubeDurationFormatter;
use App\Modules\YouTube\Support\YouTubeUrlBuilder;
use Tests\TestCase;

class YouTubeAnalyticsSummaryActionTest extends TestCase
{
    public function test_channel_handle_is_resolved_before_video_search(): void
    {
        $gateway = new FakeYouTubeAnalyticsGateway();
        $action = new AnalyticsSummaryAction(
            $gateway,
            new YouTubeAnalyticsReportBuilder(),
            new YouTubeVideoPresenter(new YouTubeDurationFormatter(), new YouTubeUrlBuilder()),
            new YouTubeChannelPresenter(new YouTubeUrlBuilder()),
            new YouTubeChannelResolver($gateway, new YouTubeChannelInputNormalizer()),
        );

        $summary = $action->handle(new YouTubeAnalyticsLookupDTO('channel', '', '@yoj996', 3));

        $this->assertSame(FakeYouTubeAnalyticsGateway::CHANNEL_ID, $summary['channelId']);
        $this->assertSame('Resolved channel', $summary['channel']['title']);
        $this->assertSame(FakeYouTubeAnalyticsGateway::CHANNEL_ID, $gateway->searchCalls[0]['channelId']);
    }
}

class FakeYouTubeAnalyticsGateway implements YouTubeGatewayInterface
{
    public const CHANNEL_ID = 'UCresolved12345678901234';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $searchCalls = [];

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
        if (isset($params['forHandle'])) {
            return ['items' => [['id' => self::CHANNEL_ID]]];
        }

        return [
            'items' => [[
                'id' => self::CHANNEL_ID,
                'snippet' => [
                    'title' => 'Resolved channel',
                    'publishedAt' => '2024-01-01T00:00:00Z',
                ],
                'statistics' => [],
            ]],
        ];
    }

    public function commentThreads(array $params): array
    {
        return ['items' => []];
    }

    public function comments(array $params): array
    {
        return ['items' => []];
    }
}
