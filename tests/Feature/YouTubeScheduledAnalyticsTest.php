<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Exceptions\Public\PublicResourceNotFoundException;
use App\Modules\ParserSupport\ParserRunSourceRequestBudget;
use App\Modules\YouTube\Analytics\Reports\ScheduledYouTubeAnalytics;
use App\Modules\YouTube\Analytics\YouTubeAnalyticsReportBuilder;
use App\Modules\YouTube\Presenters\YouTubeChannelPresenter;
use App\Modules\YouTube\Presenters\YouTubeVideoPresenter;
use App\Modules\YouTube\Support\YouTubeApiConfig;
use App\Modules\YouTube\Support\YouTubeChannelInputNormalizer;
use App\Modules\YouTube\Support\YouTubeChannelResolver;
use App\Modules\YouTube\Support\YouTubeDurationFormatter;
use App\Modules\YouTube\Support\YouTubeUrlBuilder;
use App\Modules\YouTube\YouTubeDataApiClient;
use App\Support\Observability\ExternalServiceLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YouTubeScheduledAnalyticsTest extends TestCase
{
    private const CHANNEL_ID = 'UC0123456789012345678901';

    private const UPLOADS_ID = 'UU0123456789012345678901';

    private const API_URL = 'https://www.googleapis.com/youtube/v3';

    public function test_report_includes_more_than_fifty_uploads_without_search_api_truncation(): void
    {
        $videos = array_map(fn (int $number): array => $this->video($number, '2026-10-08T12:00:00Z', $number), range(1, 51));
        $this->fakeApi([
            ['items' => array_map($this->playlistItem(...), array_slice($videos, 0, 50)), 'nextPageToken' => 'page-2'],
            ['items' => [$this->playlistItem($videos[50])]],
        ], $videos);

        $report = $this->build();

        $this->assertSame(51, $report['totals']['videos']);
        $this->assertSame(1326, $report['totals']['views']);
        $this->assertSame(51, $report['distribution']['timeline'][0]['videos']);
        $this->assertSame('channel', $report['mode']);
        $this->assertNull($report['video']);
        $this->assertSame(self::CHANNEL_ID, $report['channelId']);
        $this->assertSame(self::CHANNEL_ID, $report['range']['channelInput']);
        $this->assertSame('youtube_data_api', $report['methodology']['source']);
        $this->assertSame('lifetime_statistics_of_public_videos_published_in_range', $report['methodology']['metricsBasis']);
        Http::assertSentCount(5);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/search'));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/playlistItems') && ($request->data()['pageToken'] ?? null) === 'page-2');
    }

    public function test_range_boundaries_are_inclusive_and_timeline_uses_selected_timezone(): void
    {
        $from = CarbonImmutable::parse('2026-10-08', 'Europe/Moscow')->startOfDay();
        $to = $from->endOfDay();
        $videos = [
            $this->video(1, $from->subSecond()->toISOString(), 1000),
            $this->video(2, $from->toISOString(), 10),
            $this->video(3, $to->toISOString(), 20),
            $this->video(4, $to->addMicrosecond()->toISOString(), 2000),
        ];
        $this->fakeApi([['items' => array_map($this->playlistItem(...), $videos)]], $videos);

        $report = $this->service()->build(self::CHANNEL_ID, $from->utc(), $to->utc(), 'Europe/Moscow');

        $this->assertSame(2, $report['totals']['videos']);
        $this->assertSame(30, $report['totals']['views']);
        $this->assertSame('2026-10-08', $report['distribution']['timeline'][0]['key']);
        $this->assertCount(1, $report['distribution']['timeline']);
        $this->assertSame('Europe/Moscow', $report['range']['timezone']);
        $this->assertSame(1, $report['range']['periodDays']);
        $this->assertTrue(CarbonImmutable::parse($report['range']['dateFrom'])->equalTo($from));
        $this->assertTrue(CarbonImmutable::parse($report['range']['dateTo'])->equalTo($to));
    }

    public function test_calendar_month_metadata_has_thirty_one_days_and_preserves_immutable_range(): void
    {
        $from = CarbonImmutable::parse('2026-10-01', 'Europe/Moscow')->startOfDay()->utc();
        $to = CarbonImmutable::parse('2026-10-31', 'Europe/Moscow')->endOfDay()->utc();
        $this->fakeApi([['items' => []]]);

        $report = $this->service()->build(self::CHANNEL_ID, $from, $to, 'Europe/Moscow');

        $this->assertSame(31, $report['range']['periodDays']);
        $this->assertTrue(CarbonImmutable::parse($report['range']['dateFrom'])->equalTo($from));
        $this->assertTrue(CarbonImmutable::parse($report['range']['dateTo'])->equalTo($to));
        $this->assertSame('2026-09-30T21:00:00.000000Z', $from->toISOString());
        $this->assertSame('2026-10-31T20:59:59.999999Z', $to->toISOString());
    }

    public function test_adding_an_old_video_to_a_playlist_does_not_change_its_publication_period(): void
    {
        $old = $this->video(1, '2020-01-01T10:00:00Z', 5000);
        $item = $this->playlistItem($old);
        $item['snippet']['publishedAt'] = '2026-10-08T10:00:00Z';
        $this->fakeApi([['items' => [$item]]], [$old]);

        $report = $this->build();

        $this->assertSame(0, $report['totals']['videos']);
        $this->assertSame(0, $report['totals']['views']);
    }

    public function test_later_pages_are_read_after_an_old_video_and_duplicate_uploads_are_counted_once(): void
    {
        $old = $this->video(1, '2020-01-01T10:00:00Z');
        $new = $this->video(2, '2026-10-08T10:00:00Z', 500);
        $this->fakeApi([
            ['items' => [$this->playlistItem($old)], 'nextPageToken' => 'page-2'],
            ['items' => [$this->playlistItem($new), $this->playlistItem($new)]],
        ], [$old, $new]);

        $report = $this->build();

        $this->assertSame(1, $report['totals']['videos']);
        $this->assertSame(500, $report['totals']['views']);
    }

    public function test_empty_uploads_page_with_a_next_token_does_not_stop_collection(): void
    {
        $video = $this->video(1, '2026-10-08T12:00:00Z');
        $this->fakeApi([
            ['items' => [], 'nextPageToken' => 'page-2'],
            ['items' => [$this->playlistItem($video)]],
        ], [$video]);

        $report = $this->build();

        $this->assertSame(1, $report['totals']['videos']);
    }

    public function test_incomplete_collection_at_page_cap_fails_instead_of_returning_a_partial_report(): void
    {
        config()->set('youtube_analytics_reports.fetch_max_pages', 1);
        $video = $this->video(1, '2026-10-08T12:00:00Z');
        $this->fakeApi([['items' => [$this->playlistItem($video)], 'nextPageToken' => 'page-2']], [$video]);

        $this->expectException(ExternalServiceRequestException::class);
        $this->expectExceptionMessage('youtube_analytics_reports.errors.collection_limit');

        $this->build();
    }

    public function test_repeating_page_token_fails_instead_of_returning_a_partial_report(): void
    {
        $this->fakeApi([
            ['items' => [], 'nextPageToken' => 'A'],
            ['items' => [], 'nextPageToken' => 'B'],
            ['items' => [], 'nextPageToken' => 'A'],
        ]);

        $this->expectException(ExternalServiceRequestException::class);
        $this->expectExceptionMessage('youtube_analytics_reports.errors.pagination_stalled');

        $this->build();
    }

    public static function malformedPageTokens(): array
    {
        return [
            'empty' => [''],
            'whitespace' => [' '],
            'control character' => ["page\n2"],
            'number' => [4],
            'array' => [['page-2']],
            'null' => [null],
        ];
    }

    #[DataProvider('malformedPageTokens')]
    public function test_malformed_page_token_cannot_mark_collection_complete(mixed $token): void
    {
        $this->fakeApi([['items' => [], 'nextPageToken' => $token]]);

        $this->expectException(ExternalServiceRequestException::class);
        $this->expectExceptionMessage('youtube_analytics_reports.errors.pagination_stalled');

        $this->build();
    }

    public static function malformedUploads(): array
    {
        return [
            'missing items' => [[]],
            'upstream error' => [['error' => ['message' => 'failed']]],
            'non-list items' => [['items' => ['video' => []]]],
            'bad video id' => [['items' => [['contentDetails' => ['videoId' => 'broken', 'videoPublishedAt' => '2026-10-08T12:00:00Z']]]]],
            'missing publication date' => [['items' => [['contentDetails' => ['videoId' => 'vid00000001']]]]],
            'bad publication date' => [['items' => [['contentDetails' => ['videoId' => 'vid00000001', 'videoPublishedAt' => 'not-a-date']]]]],
            'normalized invalid calendar date' => [['items' => [['contentDetails' => ['videoId' => 'vid00000001', 'videoPublishedAt' => '2026-02-31T10:00:00Z']]]]],
        ];
    }

    #[DataProvider('malformedUploads')]
    public function test_malformed_uploads_payload_fails_instead_of_faking_an_empty_report(array $payload): void
    {
        $this->fakeApi([$payload]);

        $this->expectException(ExternalServiceUnavailableException::class);
        $this->expectExceptionMessage('errors.api.youtube.request_failed');

        $this->build();
    }

    public static function malformedVideoStatistics(): array
    {
        return [
            'missing statistics' => [null],
            'missing views' => [['likeCount' => '1', 'commentCount' => '1']],
            'null optional count' => [['viewCount' => '10', 'likeCount' => null, 'commentCount' => '1']],
            'negative count' => [['viewCount' => '-10', 'likeCount' => '1', 'commentCount' => '1']],
            'non-integer count' => [['viewCount' => '10.5', 'likeCount' => '1', 'commentCount' => '1']],
            'nonnumeric count' => [['viewCount' => 'unknown', 'likeCount' => '1', 'commentCount' => '1']],
        ];
    }

    #[DataProvider('malformedVideoStatistics')]
    public function test_missing_or_invalid_statistics_do_not_become_zeroes(?array $statistics): void
    {
        $video = $this->video(1, '2026-10-08T12:00:00Z');
        $item = $this->playlistItem($video);
        $video['statistics'] = $statistics;
        $this->fakeApi([['items' => [$item]]], [$video]);

        $this->expectException(ExternalServiceUnavailableException::class);

        $this->build();
    }

    public static function unavailableEngagementCounts(): array
    {
        return [
            'hidden likes' => [['likeCount'], 1, 0],
            'disabled comments' => [['commentCount'], 0, 1],
            'both unavailable' => [['likeCount', 'commentCount'], 1, 1],
        ];
    }

    #[DataProvider('unavailableEngagementCounts')]
    public function test_unavailable_optional_metrics_are_explicitly_null_with_known_totals(array $missingFields, int $missingLikes, int $missingComments): void
    {
        $known = $this->video(1, '2026-10-08T12:00:00Z');
        $unavailable = $this->video(2, '2026-10-08T13:00:00Z');
        foreach ($missingFields as $field) {
            unset($unavailable['statistics'][$field]);
        }
        $videos = [$known, $unavailable];
        $this->fakeApi([['items' => array_map($this->playlistItem(...), $videos)]], $videos);

        $report = $this->build();

        $this->assertSame(2, $report['totals']['videos']);
        $this->assertSame(200, $report['totals']['views']);
        $this->assertSame($missingLikes > 0 ? null : 20, $report['totals']['likes']);
        $this->assertSame($missingComments > 0 ? null : 4, $report['totals']['comments']);
        $this->assertSame($missingLikes > 0 ? 10 : 20, $report['totals']['likesKnown']);
        $this->assertSame($missingComments > 0 ? 2 : 4, $report['totals']['commentsKnown']);
        $this->assertNull($report['totals']['engagementRate']);
        $this->assertSame(['likes' => $missingLikes, 'comments' => $missingComments], $report['methodology']['missingMetricCounts']);
        $this->assertFalse($report['methodology']['statisticsComplete']);
        $this->assertSame($missingLikes > 0 ? null : 20, $report['distribution']['timeline'][0]['likes']);
        $this->assertSame($missingComments > 0 ? null : 4, $report['distribution']['timeline'][0]['comments']);
        $this->assertCount($missingLikes > 0 ? 1 : 2, $report['leaders']['byLikes']);
        $this->assertCount($missingComments > 0 ? 1 : 2, $report['leaders']['byComments']);
        $this->assertCount(1, $report['leaders']['byEngagement']);
        $this->assertNotContains('engagement', array_column($report['insights'], 'key'));
        $video = array_values(array_filter($report['topVideos'], fn (array $video): bool => $video['id'] === $unavailable['id']))[0];
        $this->assertSame($missingLikes > 0 ? null : 10, $video['likes']);
        $this->assertSame($missingComments > 0 ? null : 2, $video['comments']);
        $this->assertNull($video['engagementRate']);
    }

    public function test_missing_requested_video_does_not_become_a_successful_partial_snapshot(): void
    {
        $video = $this->video(1, '2026-10-08T12:00:00Z');
        $this->fakeApi([['items' => [$this->playlistItem($video)]]], []);

        $this->expectException(ExternalServiceUnavailableException::class);

        $this->build();
    }

    public function test_video_from_another_channel_cannot_be_included(): void
    {
        $video = $this->video(1, '2026-10-08T12:00:00Z');
        $video['snippet']['channelId'] = 'UCabcdefghijklmnopqrstuv';
        $this->fakeApi([['items' => [$this->playlistItem($video)]]], [$video]);

        $this->expectException(ExternalServiceUnavailableException::class);

        $this->build();
    }

    public function test_non_public_videos_do_not_enter_public_analytics(): void
    {
        $video = $this->video(1, '2026-10-08T12:00:00Z', 1000);
        $video['status']['privacyStatus'] = 'private';
        $this->fakeApi([['items' => [$this->playlistItem($video)]]], [$video]);

        $report = $this->build();

        $this->assertSame(0, $report['totals']['videos']);
        $this->assertSame(0, $report['totals']['views']);
    }

    public function test_public_channel_must_exist_even_when_a_direct_channel_id_is_supplied(): void
    {
        $this->fakeApi([['items' => []]], channelPayload: ['items' => []]);

        $this->expectException(PublicResourceNotFoundException::class);

        $this->build();
    }

    public function test_channel_handle_is_resolved_before_uploads_are_loaded(): void
    {
        $this->fakeApi([['items' => []]]);

        $report = $this->service()->build('@known-channel', CarbonImmutable::parse('2026-10-08')->startOfDay(), CarbonImmutable::parse('2026-10-08')->endOfDay(), 'UTC');

        $this->assertSame(self::CHANNEL_ID, $report['channelId']);
        Http::assertSent(fn (Request $request): bool => ($request->data()['forHandle'] ?? null) === '@known-channel');
    }

    public function test_malformed_channel_resolution_response_is_retried_instead_of_treated_as_missing(): void
    {
        $this->fakeApi([['items' => []]], channelPayload: []);

        $this->expectException(ExternalServiceUnavailableException::class);

        $this->service()->build('@known-channel', CarbonImmutable::parse('2026-10-08')->startOfDay(), CarbonImmutable::parse('2026-10-08')->endOfDay(), 'UTC');
    }

    public function test_private_and_deleted_upload_placeholders_without_public_dates_are_skipped(): void
    {
        $video = $this->video(1, '2026-10-08T12:00:00Z');
        $this->fakeApi([['items' => [
            ['snippet' => ['title' => 'Private video'], 'contentDetails' => ['videoId' => 'vid00000002'], 'status' => ['privacyStatus' => 'private']],
            ['snippet' => ['title' => 'Deleted video'], 'contentDetails' => ['videoId' => 'vid00000003'], 'status' => ['privacyStatus' => 'private']],
            $this->playlistItem($video),
        ]]], [$video]);

        $report = $this->build();

        $this->assertSame(1, $report['totals']['videos']);
        $this->assertSame(100, $report['totals']['views']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/videos') && $request['id'] === $video['id']);
    }

    public function test_range_day_count_uses_local_calendar_across_daylight_saving_transition(): void
    {
        $from = CarbonImmutable::parse('2026-10-31', 'America/New_York')->startOfDay();
        $to = CarbonImmutable::parse('2026-11-02', 'America/New_York')->endOfDay();
        $this->fakeApi([['items' => []]]);

        $report = $this->service()->build(self::CHANNEL_ID, $from->utc(), $to->utc(), 'America/New_York');

        $this->assertSame(3, $report['range']['periodDays']);
        $this->assertTrue(CarbonImmutable::parse($report['range']['dateFrom'])->equalTo($from));
        $this->assertTrue(CarbonImmutable::parse($report['range']['dateTo'])->equalTo($to));
    }

    public function test_http_failure_remains_an_external_service_failure(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::API_URL.'/channels*' => Http::response(['error' => ['message' => 'upstream down']], 503)]);

        $this->expectException(ExternalServiceRequestException::class);
        $this->expectExceptionMessage('errors.api.youtube.request_failed');

        $this->build();
    }

    private function build(): array
    {
        return $this->service()->build(self::CHANNEL_ID, CarbonImmutable::parse('2026-10-08', 'UTC')->startOfDay(), CarbonImmutable::parse('2026-10-08', 'UTC')->endOfDay(), 'UTC');
    }

    private function service(): ScheduledYouTubeAnalytics
    {
        $client = new YouTubeDataApiClient(
            YouTubeApiConfig::fromArray(['key' => 'test-key', 'retry_attempts' => 0]),
            app(ExternalServiceLogger::class),
            app(ParserRunSourceRequestBudget::class),
        );

        return new ScheduledYouTubeAnalytics(
            $client,
            $client,
            new YouTubeChannelResolver($client, new YouTubeChannelInputNormalizer),
            new YouTubeVideoPresenter(new YouTubeDurationFormatter, new YouTubeUrlBuilder),
            new YouTubeChannelPresenter(new YouTubeUrlBuilder),
            new YouTubeAnalyticsReportBuilder,
        );
    }

    private function fakeApi(array $pages, array $videos = [], ?array $channelPayload = null): void
    {
        Http::preventStrayRequests();
        $playlistResponses = Http::sequence();
        foreach ($pages as $page) {
            $playlistResponses->push($page);
        }
        $channelPayload ??= [
            'items' => [[
                'id' => self::CHANNEL_ID,
                'snippet' => ['title' => 'Public channel', 'publishedAt' => '2020-01-01T00:00:00Z'],
                'contentDetails' => ['relatedPlaylists' => ['uploads' => self::UPLOADS_ID]],
                'statistics' => ['viewCount' => '10000', 'videoCount' => '51', 'subscriberCount' => '100'],
                'status' => ['privacyStatus' => 'public'],
            ]],
        ];
        Http::fake([
            self::API_URL.'/channels*' => Http::response($channelPayload),
            self::API_URL.'/playlistItems*' => $playlistResponses,
            self::API_URL.'/videos*' => function (Request $request) use ($videos) {
                $ids = explode(',', (string) $request['id']);

                return Http::response(['items' => array_values(array_filter($videos, fn (array $video): bool => in_array($video['id'], $ids, true)))]);
            },
        ]);
    }

    private function video(int $number, string $publishedAt, int $views = 100): array
    {
        return [
            'id' => 'vid'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'snippet' => [
                'title' => 'Video '.$number,
                'channelId' => self::CHANNEL_ID,
                'publishedAt' => $publishedAt,
                'tags' => ['analytics'],
            ],
            'contentDetails' => ['duration' => 'PT5M', 'definition' => 'hd', 'caption' => 'false'],
            'statistics' => ['viewCount' => (string) $views, 'likeCount' => '10', 'commentCount' => '2'],
            'status' => ['privacyStatus' => 'public'],
        ];
    }

    private function playlistItem(array $video): array
    {
        return [
            'snippet' => ['publishedAt' => $video['snippet']['publishedAt'], 'resourceId' => ['videoId' => $video['id']]],
            'contentDetails' => ['videoId' => $video['id'], 'videoPublishedAt' => $video['snippet']['publishedAt']],
            'status' => ['privacyStatus' => 'public'],
        ];
    }
}
