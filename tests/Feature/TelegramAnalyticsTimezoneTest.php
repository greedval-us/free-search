<?php

namespace Tests\Feature;

use App\Modules\Telegram\Analytics\TelegramAnalyticsService;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramAnalyticsTimezoneTest extends TestCase
{
    public static function timezones(): array
    {
        return [['Europe/Moscow'], ['America/New_York'], ['Pacific/Honolulu'], ['UTC']];
    }

    #[DataProvider('timezones')]
    public function test_full_local_day_uses_the_same_timezone_for_hourly_activity_audience_and_opinion_leaders(string $timezone): void
    {
        $report = $this->buildReport($timezone, '2026-10-09', '2026-10-09', [
            ['2026-10-09 00:05:00', 101],
            ['2026-10-09 23:45:00', 101],
            ['2026-10-09 23:55:00', 102],
        ]);
        $summary = $report['summary'];
        $buckets = array_column($summary['timeline'], 'messages', 'key');

        $this->assertSame('hour', $report['range']['groupBy']);
        $this->assertSame(3, $summary['totals']['messages']);
        $this->assertSame(3, array_sum($buckets));
        $this->assertSame(1, $buckets['2026-10-09 00:00']);
        $this->assertSame(2, $buckets['2026-10-09 23:00']);
        $this->assertSame(23, $summary['audience']['mostActiveHours'][0]['hour']);
        $this->assertSame(2, $summary['audience']['mostActiveHours'][0]['messages']);
        $this->assertSame(['2026-10-09'], array_values(array_unique(array_column($summary['opinionLeadersDaily'], 'dayKey'))));
        $this->assertSame([101 => 2, 102 => 1], array_column($summary['opinionLeadersDaily'], 'messages', 'authorId'));
    }

    #[DataProvider('timezones')]
    public function test_multiple_local_days_keep_midnight_messages_in_the_correct_daily_distribution(string $timezone): void
    {
        $report = $this->buildReport($timezone, '2026-10-09', '2026-10-11', [
            ['2026-10-09 00:05:00', 101],
            ['2026-10-11 00:05:00', 101],
            ['2026-10-11 23:45:00', 102],
        ]);
        $summary = $report['summary'];

        $this->assertSame('day', $report['range']['groupBy']);
        $this->assertSame(3, $report['range']['periodDays']);
        $this->assertSame(3, $summary['totals']['messages']);
        $this->assertSame(['2026-10-09' => 1, '2026-10-10' => 0, '2026-10-11' => 2], array_column($summary['timeline'], 'messages', 'key'));
        $this->assertSame(0, $summary['audience']['mostActiveHours'][0]['hour']);
        $this->assertSame(2, $summary['audience']['mostActiveHours'][0]['messages']);
        $this->assertSame(['2026-10-09', '2026-10-11'], array_values(array_unique(array_column($summary['opinionLeadersDaily'], 'dayKey'))));
        $this->assertSame(3, array_sum(array_column($summary['opinionLeadersDaily'], 'messages')));
    }

    private function buildReport(string $timezone, string $from, string $to, array $messages): array
    {
        Http::preventStrayRequests();
        $data = array_map(static fn (array $message, int $index): array => [
            '_' => 'message', 'id' => $index + 1,
            'date' => Carbon::parse($message[0], $timezone)->timestamp,
            'message' => 'Public group message',
            'from_id' => ['_' => 'peerUser', 'user_id' => $message[1]],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 500],
            'views' => 10, 'forwards' => 1,
        ], $messages, array_keys($messages));
        usort($data, static fn (array $left, array $right): int => $right['date'] <=> $left['date']);
        $this->mock(TelegramGatewayInterface::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getMessages')->twice()->andReturn(new ChannelMessagesDTO([
                '_' => 'messages.messages', 'messages' => $data, 'count' => count($data),
            ]), new ChannelMessagesDTO(['_' => 'messages.messages', 'messages' => [], 'count' => 0]));
            $mock->shouldReceive('getInfo')->once()->andReturnNull();
        });

        return app(TelegramAnalyticsService::class)->build(
            'publicgroup', Carbon::parse($from, $timezone)->startOfDay(), Carbon::parse($to, $timezone)->endOfDay(),
        );
    }
}
