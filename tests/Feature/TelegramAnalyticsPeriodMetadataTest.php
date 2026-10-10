<?php

namespace Tests\Feature;

use App\Modules\Telegram\Analytics\TelegramAnalyticsService;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramAnalyticsPeriodMetadataTest extends TestCase
{
    public static function ranges(): array
    {
        return [
            'one complete day' => ['2026-10-08', '2026-10-08', 1],
            'three complete days' => ['2026-10-06', '2026-10-08', 3],
            'seven complete days' => ['2026-10-02', '2026-10-08', 7],
            'calendar month' => ['2026-10-01', '2026-10-31', 31],
        ];
    }

    #[DataProvider('ranges')]
    public function test_report_metadata_uses_actual_calendar_days(string $from, string $to, int $days): void
    {
        $this->mock(TelegramGatewayInterface::class, function ($mock): void {
            $mock->shouldReceive('getMessages')->once()->andReturn(new ChannelMessagesDTO(['_' => 'messages.messages', 'messages' => []]));
            $mock->shouldReceive('getInfo')->once()->andReturnNull();
        });

        $report = app(TelegramAnalyticsService::class)->build(
            'publicgroup', Carbon::parse($from, 'Europe/Moscow')->startOfDay(), Carbon::parse($to, 'Europe/Moscow')->endOfDay(),
        );

        $this->assertSame($days, $report['range']['periodDays']);
    }
}
