<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\DTO\Request\YouTubeParserStartDTO;
use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;
use App\Modules\YouTube\DTO\Result\YouTubeAnalyticsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeCommentsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeParserRunStatusDTO;
use App\Modules\YouTube\DTO\Result\YouTubeSearchResultDTO;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Feature\Controllers\Concerns\CreatesPaidUser;
use Tests\Feature\Controllers\Concerns\MocksYouTubeServices;
use Tests\TestCase;

class YouTubeControllerIsolationTest extends TestCase
{
    use CreatesPaidUser;
    use MocksYouTubeServices;
    use RefreshDatabase;

    public function test_youtube_search_controller_returns_service_payload(): void
    {
        $user = User::factory()->create();

        $this->mock(YouTubeSearchApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('searchVideos')
                ->once()
                ->with(Mockery::on(
                    fn (YouTubeSearchQueryDTO $query): bool => $query->query === 'open source'
                        && $query->type === 'video'
                        && $query->maxResults === 3
                ))
                ->andReturn(new YouTubeSearchResultDTO([
                    'items' => [
                        ['id' => 'video-1', 'title' => 'Open source intelligence'],
                    ],
                    'nextPageToken' => 'next-token',
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.search.videos', [
                'q' => 'open source',
                'limit' => 3,
            ]))
            ->assertOk()
            ->assertJsonPath('data.items.0.id', 'video-1')
            ->assertJsonPath('data.nextPageToken', 'next-token');
    }

    public function test_youtube_search_controller_maps_runtime_exception_to_json_error(): void
    {
        $user = User::factory()->create();

        $this->mockYouTubeSearchError('YouTube quota exceeded.', 429);

        $this
            ->actingAs($user)
            ->getJson(route('youtube.search.videos', ['q' => 'open source']))
            ->assertTooManyRequests()
            ->assertJson([
                'ok' => false,
                'message' => 'YouTube quota exceeded.',
            ]);
    }

    public function test_youtube_analytics_summary_controller_returns_service_payload(): void
    {
        $user = $this->paidUser();

        $this->mock(YouTubeAnalyticsApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('summary')
                ->once()
                ->with(Mockery::on(
                    fn (YouTubeAnalyticsLookupDTO $query): bool => $query->mode === 'video'
                        && $query->videoId === 'video123'
                ))
                ->andReturn(new YouTubeAnalyticsResultDTO([
                    'videoId' => 'video123',
                    'views' => 1234,
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.analytics.summary', [
                'mode' => 'video',
                'videoId' => 'video123',
            ]))
            ->assertOk()
            ->assertJsonPath('data.videoId', 'video123')
            ->assertJsonPath('data.views', 1234);
    }

    public function test_youtube_parser_comments_controller_maps_request_to_service(): void
    {
        $user = $this->paidUser();

        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('comments')
                ->once()
                ->with(Mockery::on(
                    fn (YouTubeCommentsQueryDTO $query): bool => $query->videoId === 'abc123'
                        && $query->maxResults === 4
                        && $query->order === 'time'
                ))
                ->andReturn(new YouTubeCommentsResultDTO([
                    'items' => [['id' => 'comment-1']],
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.parser.comments', [
                'videoId' => 'abc123',
                'limit' => 4,
                'order' => 'time',
            ]))
            ->assertOk()
            ->assertJsonPath('data.items.0.id', 'comment-1');
    }

    public function test_youtube_search_comments_preview_controller_maps_request_to_service(): void
    {
        $user = User::factory()->create();

        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('comments')
                ->once()
                ->with(Mockery::on(
                    fn (YouTubeCommentsQueryDTO $query): bool => $query->videoId === 'abc123'
                        && $query->maxResults === 4
                        && $query->order === 'time'
                ))
                ->andReturn(new YouTubeCommentsResultDTO([
                    'items' => [['id' => 'comment-preview-1']],
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.search.comments-preview', [
                'videoId' => 'abc123',
                'limit' => 4,
                'order' => 'time',
            ]))
            ->assertOk()
            ->assertJsonPath('data.items.0.id', 'comment-preview-1');
    }

    public function test_youtube_parser_start_controller_passes_authenticated_user_to_service(): void
    {
        $user = $this->paidUser();

        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock) use ($user): void {
            $mock->shouldReceive('start')
                ->once()
                ->with(Mockery::on(
                    fn (YouTubeParserStartDTO $input): bool => $input->userId === $user->id
                        && $input->videoId === 'video123'
                ))
                ->andReturn(new YouTubeParserRunStatusDTO([
                    'ok' => true,
                    'runId' => 'yt-run-1',
                    'status' => 'queued',
                ]));
        });

        $this
            ->actingAs($user)
            ->postJson(route('youtube.parser.start'), ['videoId' => 'video123'])
            ->assertOk()
            ->assertJsonPath('runId', 'yt-run-1')
            ->assertJsonPath('status', 'queued');
    }

    public function test_youtube_parser_status_controller_returns_service_payload(): void
    {
        $user = $this->paidUser();

        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock) use ($user): void {
            $mock->shouldReceive('status')
                ->once()
                ->with($user->id, 'yt-run-1')
                ->andReturn(new YouTubeParserRunStatusDTO([
                    'ok' => true,
                    'runId' => 'yt-run-1',
                    'status' => 'done',
                ]));
        });

        $this
            ->actingAs($user)
            ->getJson(route('youtube.parser.status', ['runId' => 'yt-run-1']))
            ->assertOk()
            ->assertJsonPath('runId', 'yt-run-1')
            ->assertJsonPath('status', 'done');
    }
}
