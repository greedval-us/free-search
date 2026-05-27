<?php

namespace Tests\Feature\Controllers\Concerns;

use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsApplicationServiceInterface;
use App\Modules\Telegram\Analytics\Contracts\TelegramAnalyticsRangeResolverInterface;
use App\Modules\Telegram\DTO\Result\AnalyticsSummaryResultDTO;
use App\Modules\Telegram\DTO\Result\ParserRunStatusDTO;
use App\Modules\Telegram\DTO\Result\SearchCommentsResultDTO;
use App\Modules\Telegram\DTO\Result\SearchMessagesResultDTO;
use App\Modules\Telegram\Parser\Contracts\TelegramParserApplicationServiceInterface;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Support\Contracts\TelegramMediaResponderInterface;
use Carbon\Carbon;

trait MocksTelegramServices
{
    private function mockTelegramControllerBaseDeps(): void
    {
        $this->mock(TelegramMediaResponderInterface::class);
    }

    private function mockTelegramSearchMessages(SearchMessagesResultDTO $result): void
    {
        $this->mock(TelegramSearchApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('messages')->once()->andReturn($result);
        });
    }

    private function mockTelegramSearchComments(SearchCommentsResultDTO $result): void
    {
        $this->mock(TelegramSearchApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('comments')->once()->andReturn($result);
        });
    }

    private function mockTelegramAnalyticsRange(Carbon $from, Carbon $to): void
    {
        $this->mock(TelegramAnalyticsRangeResolverInterface::class, function ($mock) use ($from, $to): void {
            $mock->shouldReceive('resolveRange')->once()->andReturn(['from' => $from, 'to' => $to]);
        });
    }

    private function mockTelegramAnalyticsSummary(AnalyticsSummaryResultDTO $result): void
    {
        $this->mock(TelegramAnalyticsApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('buildSummary')->once()->andReturn($result);
        });
    }

    private function mockTelegramParserStart(ParserRunStatusDTO $result): void
    {
        $this->mock(TelegramParserApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('start')->once()->andReturn($result);
        });
    }

    private function mockTelegramParserStatus(?ParserRunStatusDTO $result): void
    {
        $this->mock(TelegramParserApplicationServiceInterface::class, function ($mock) use ($result): void {
            $mock->shouldReceive('status')->once()->andReturn($result);
        });
    }
}
