<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Models\LinkRequest;
use App\MoonShine\Support\TelegramBotAnalyticsService;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\TelegramBot\TelegramBotTestCase;

final class TelegramBotAnalyticsTest extends TelegramBotTestCase
{
    #[Test]
    public function snapshot_counts_retained_records_in_the_selected_creation_window(): void
    {
        $this->freezeTime();
        $link = $this->linkedUser('10001', ['exports_enabled' => true]);
        $older = $this->linkedUser('10002', ['notifications_enabled' => false, 'broadcasts_enabled' => true]);
        $older->update(['created_at' => now()->subDays(8)]);
        LinkRequest::query()->create(['user_id' => $link->user_id, 'token_hash' => 'active', 'locale' => 'en', 'expires_at' => now()->addMinute()]);
        LinkRequest::query()->create(['user_id' => $older->user_id, 'token_hash' => 'expired', 'locale' => 'en', 'expires_at' => now()->subMinute()]);
        foreach ([BotDelivery::SENT, BotDelivery::FAILED, BotDelivery::PENDING, BotDelivery::SKIPPED] as $status) {
            $this->delivery($link, ['status' => $status]);
        }
        $this->delivery($link, ['created_at' => now()->subDays(8)]);
        $this->delivery($link, ['created_at' => now()->addDay()]);
        $snapshot = app(TelegramBotAnalyticsService::class)->snapshot(7);

        self::assertSame(2, $snapshot['links']);
        self::assertSame(1, $snapshot['new_links']);
        self::assertSame(1, $snapshot['pending_links']);
        self::assertSame(['notifications' => 1, 'exports' => 1, 'broadcasts' => 1], $snapshot['consents']);
        self::assertSame(4, $snapshot['deliveries']);
        self::assertSame(25.0, $snapshot['sent_share']);
        self::assertCount(7, $snapshot['daily']);
        self::assertSame(4, $snapshot['daily'][6]['total']);
        self::assertSame(1, $snapshot['daily'][6]['sent']);
        self::assertSame(0, $snapshot['daily'][0]['total']);
        self::assertTrue($snapshot['configured']);
    }

    #[Test]
    public function empty_snapshot_clamps_periods_to_retention_and_handles_disabled_bot(): void
    {
        config()->set(['telegram_bot.delivery_retention_days' => 3, 'telegram_bot.enabled' => false]);
        $snapshot = app(TelegramBotAnalyticsService::class)->snapshot(999);

        self::assertSame([3], $snapshot['periods']);
        self::assertSame(3, $snapshot['period']);
        self::assertSame(0, $snapshot['links']);
        self::assertSame(0, $snapshot['deliveries']);
        self::assertNull($snapshot['sent_share']);
        self::assertFalse($snapshot['configured']);
        self::assertCount(3, $snapshot['daily']);
    }

    #[Test]
    public function unknown_kinds_and_statuses_are_counted_without_showing_raw_values(): void
    {
        $link = $this->linkedUser();
        $this->delivery($link, ['kind' => 'private-kind', 'status' => 'private-status']);
        $snapshot = app(TelegramBotAnalyticsService::class)->snapshot(7);

        self::assertSame(['label' => __('admin_panel.values.unknown'), 'count' => 1], end($snapshot['statuses']));
        self::assertSame(['label' => __('admin_panel.values.unknown'), 'count' => 1], end($snapshot['kinds']));
        self::assertStringNotContainsString('private-', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function diagnostics_reads_the_configured_backend_and_identifies_stale_deliveries(): void
    {
        $this->freezeTime();
        config()->set([
            'telegram_bot.queue.connection' => 'redis', 'telegram_bot.queue.name' => 'bot-only',
            'telegram_bot.queue.retry_window_minutes' => 12,
        ]);
        $queue = Mockery::mock(Queue::class);
        $queue->shouldReceive('size')->once()->with('bot-only')->andReturn(42);
        $this->mock(Factory::class)->shouldReceive('connection')->once()->with('redis')->andReturn($queue);
        $link = $this->linkedUser();
        $this->delivery($link, ['created_at' => now()->subMinutes(13)]);
        $this->delivery($link);
        $this->delivery($link, ['status' => BotDelivery::SENT, 'sent_at' => now()->subMinute()]);
        $diagnostics = app(TelegramBotAnalyticsService::class)->diagnostics();

        self::assertSame(42, $diagnostics['queue_size']);
        self::assertSame('available', $diagnostics['queue_state']);
        self::assertSame(2, $diagnostics['pending']);
        self::assertSame(1, $diagnostics['stale']);
        self::assertSame(now()->subMinutes(13)->toDateTimeString(), $diagnostics['oldest_pending_at']->toDateTimeString());
        self::assertSame(now()->subMinute()->toDateTimeString(), $diagnostics['last_sent_at']->toDateTimeString());
    }

    #[Test]
    public function inaccessible_backend_does_not_break_the_page_or_disclose_connection_errors(): void
    {
        config()->set('telegram_bot.queue.connection', 'redis');
        $this->mock(Factory::class)->shouldReceive('connection')->once()
            ->andThrow(new RuntimeException('SECRET_REDIS_PASSWORD'));
        $diagnostics = app(TelegramBotAnalyticsService::class)->diagnostics();

        self::assertSame('unavailable', $diagnostics['queue_state']);
        self::assertNull($diagnostics['queue_size']);
        self::assertStringNotContainsString('SECRET_REDIS_PASSWORD', json_encode($diagnostics, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function unsupported_backend_is_not_reported_as_a_healthy_worker(): void
    {
        config()->set('telegram_bot.queue.connection', 'sync');
        $this->mock(Factory::class)->shouldNotReceive('connection');
        $diagnostics = app(TelegramBotAnalyticsService::class)->diagnostics();

        self::assertSame('unsupported', $diagnostics['queue_state']);
        self::assertNull($diagnostics['queue_size']);
    }

    private function delivery(BotLink $link, array $attributes = []): BotDelivery
    {
        return BotDelivery::query()->create([
            'link_id' => $link->id, 'deduplication_key' => fake()->uuid(),
            'kind' => 'notification', 'reference' => 'test', 'payload' => [],
            ...$attributes,
        ]);
    }
}
