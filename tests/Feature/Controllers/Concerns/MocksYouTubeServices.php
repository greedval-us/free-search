<?php

namespace Tests\Feature\Controllers\Concerns;

use App\Exceptions\PublicException;
use App\Modules\YouTube\Analytics\Contracts\YouTubeAnalyticsApplicationServiceInterface;
use App\Modules\YouTube\DTO\Result\YouTubeAnalyticsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeCommentsResultDTO;
use App\Modules\YouTube\DTO\Result\YouTubeParserRunStatusDTO;
use App\Modules\YouTube\DTO\Result\YouTubeSearchResultDTO;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;
use App\Modules\YouTube\Search\Contracts\YouTubeSearchApplicationServiceInterface;
trait MocksYouTubeServices
{
    private function mockYouTubeSearch(YouTubeSearchResultDTO $result): void
    {
        $this->mock(YouTubeSearchApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('searchVideos')->once()->andReturn($result);
        });
    }

    private function mockYouTubeSearchError(string $message, int $statusCode): void
    {
        $this->mock(YouTubeSearchApplicationServiceInterface::class, function ($mock) use ($message, $statusCode): void {
            $mock->shouldReceive('searchVideos')
                ->once()
                ->andThrow(new PublicException($message, $statusCode, 'test_public_error'));
        });
    }

    private function mockYouTubeAnalyticsSummary(YouTubeAnalyticsResultDTO $result): void
    {
        $this->mock(YouTubeAnalyticsApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('summary')->once()->andReturn($result);
        });
    }

    private function mockYouTubeParserComments(YouTubeCommentsResultDTO $result): void
    {
        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('comments')->once()->andReturn($result);
        });
    }

    private function mockYouTubeParserStart(YouTubeParserRunStatusDTO $status): void
    {
        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock) use ($status): void {
            $mock->shouldReceive('start')->once()->andReturn($status);
        });
    }

    private function mockYouTubeParserStatus(YouTubeParserRunStatusDTO $status): void
    {
        $this->mock(YouTubeParserApplicationServiceInterface::class, function ($mock) use ($status): void {
            $mock->shouldReceive('status')->once()->andReturn($status);
        });
    }
}
