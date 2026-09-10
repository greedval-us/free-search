<?php

namespace Tests\Unit;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Modules\Telegram\Tracking\TrackingPageProcessor;
use Tests\TestCase;

class TrackingPageProcessorTest extends TestCase
{
    private TelegramTrackingSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 10)->startOfDay());
        config(['telegram_tracking.page_size' => 2]);
        $this->source = new TelegramTrackingSource([
            'cursor_id' => 0, 'offset_id' => 0, 'high_id' => 0,
            'collect_from' => now()->subHours(6), 'window_end' => now(),
        ]);
        $this->source->setRelation('tracking', new TelegramTracking(['mode' => 'keyword', 'query' => 'test']));
    }

    public function test_non_matches_and_service_messages_advance_the_page_without_mutating_source(): void
    {
        $before = $this->source->getAttributes();
        $page = app(TrackingPageProcessor::class)->process($this->source, [
            $this->message(8, ['message' => 'unrelated']),
            $this->message(7, ['_' => 'messageService']),
        ]);

        $this->assertFalse($page->complete);
        $this->assertSame(7, $page->offsetId);
        $this->assertSame(8, $page->highId);
        $this->assertSame([], $page->matches);
        $this->assertSame($before, $this->source->getAttributes());
    }

    public function test_cursor_boundary_completes_the_window_and_excludes_seen_messages(): void
    {
        $this->source->cursor_id = 7;
        $page = app(TrackingPageProcessor::class)->process($this->source, [$this->message(8), $this->message(7)]);

        $this->assertTrue($page->complete);
        $this->assertSame(8, $page->highId);
        $this->assertSame([[
            'message_id' => 8, 'sender_id' => '42', 'text' => 'test message', 'sent_at' => now()->subMinute()->timestamp,
        ]], $page->matches);
    }

    public function test_date_boundary_is_inclusive_and_older_message_completes_the_window(): void
    {
        $from = $this->source->collect_from->timestamp;
        $page = app(TrackingPageProcessor::class)->process($this->source, [
            $this->message(8, ['date' => $from]), $this->message(7, ['date' => $from - 1]),
        ]);

        $this->assertTrue($page->complete);
        $this->assertSame([8], array_column($page->matches, 'message_id'));
    }

    public function test_missing_and_future_dates_do_not_match_or_end_a_full_page(): void
    {
        $page = app(TrackingPageProcessor::class)->process($this->source, [
            $this->message(8, ['date' => now()->addSecond()->timestamp]), $this->message(7, ['date' => null]),
        ]);

        $this->assertFalse($page->complete);
        $this->assertSame([], $page->matches);
        $this->assertSame(7, $page->offsetId);
    }

    public function test_short_page_preserves_high_water_mark_and_includes_end_boundary(): void
    {
        $this->source->high_id = 10;
        $page = app(TrackingPageProcessor::class)->process($this->source, [$this->message(8, ['date' => now()->timestamp])]);

        $this->assertTrue($page->complete);
        $this->assertSame(10, $page->highId);
        $this->assertCount(1, $page->matches);
        $empty = app(TrackingPageProcessor::class)->process($this->source, []);
        $this->assertTrue($empty->complete);
        $this->assertSame(10, $empty->highId);
        $this->assertNull($empty->offsetId);
    }

    public function test_repeated_full_page_is_rejected_instead_of_losing_the_checkpoint(): void
    {
        $this->source->offset_id = 7;
        $this->expectException(TrackingException::class);
        $this->expectExceptionMessage('telegram_tracking.errors.pagination_stalled');

        app(TrackingPageProcessor::class)->process($this->source, [$this->message(8), $this->message(7)]);
    }

    public function test_full_page_without_valid_ids_is_rejected(): void
    {
        $this->expectException(TrackingException::class);
        $this->expectExceptionMessage('telegram_tracking.errors.pagination_stalled');

        app(TrackingPageProcessor::class)->process($this->source, [[], $this->message(0)]);
    }

    public function test_search_continues_after_short_page_and_trusts_telegram_text_matching(): void
    {
        $this->source->collection_method = TelegramTrackingSource::SEARCH;
        $page = app(TrackingPageProcessor::class)->process($this->source, [$this->message(8, ['message' => 'API-selected result'])]);

        $this->assertFalse($page->complete);
        $this->assertSame(8, $page->offsetId);
        $this->assertCount(1, $page->matches);
        $this->assertTrue(app(TrackingPageProcessor::class)->process($this->source, [])->complete);
    }

    public function test_search_keeps_overlap_matches_below_cursor_and_rechecks_time_bounds(): void
    {
        $this->source->collection_method = TelegramTrackingSource::SEARCH;
        $this->source->cursor_id = 10;
        $this->source->high_id = 10;
        $this->source->window_start = now()->subMinutes(5);
        $page = app(TrackingPageProcessor::class)->process($this->source, [
            $this->message(12, ['date' => now()->addSecond()->timestamp]),
            $this->message(9),
            $this->message(8, ['date' => now()->subMinutes(6)->timestamp]),
        ]);

        $this->assertFalse($page->complete);
        $this->assertSame([9], array_column($page->matches, 'message_id'));
        $this->assertSame(10, $page->highId);
        $this->assertSame(8, $page->offsetId);
    }

    public function test_repeated_short_search_page_is_rejected(): void
    {
        $this->source->collection_method = TelegramTrackingSource::SEARCH;
        $this->source->offset_id = 8;
        $this->expectException(TrackingException::class);
        app(TrackingPageProcessor::class)->process($this->source, [$this->message(8)]);
    }

    private function message(int $id, array $overrides = []): array
    {
        return array_replace([
            '_' => 'message', 'id' => $id, 'date' => now()->subMinute()->timestamp,
            'message' => 'test message', 'from_id' => ['_' => 'peerUser', 'user_id' => 42],
        ], $overrides);
    }
}
