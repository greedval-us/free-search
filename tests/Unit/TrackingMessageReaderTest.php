<?php

namespace Tests\Unit;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Tracking\TrackingException;
use App\Modules\Telegram\Tracking\TrackingMessageReader;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrackingMessageReaderTest extends TestCase
{
    private TelegramTrackingSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 10)->startOfDay());
        $this->source = new TelegramTrackingSource([
            'peer_id' => '-100123456789', 'cursor_id' => 50, 'offset_id' => 0, 'high_id' => 50,
            'collect_from' => now()->subDays(10), 'window_start' => now()->subHours(6), 'window_end' => now(),
            'collection_method' => TelegramTrackingSource::SEARCH,
        ]);
        $this->source->setRelation('tracking', new TelegramTracking(['mode' => 'keyword', 'query' => 'test query']));
    }

    public function test_search_uses_fixed_inclusive_time_bounds_and_keeps_overlap_below_cursor(): void
    {
        $expected = [
            'peer' => -100123456789, 'offset_id' => 0, 'limit' => 100, 'floodWaitLimit' => 0,
            'q' => 'test query', 'filter' => ['_' => 'inputMessagesFilterEmpty'], 'hash' => [],
            'min_date' => $this->source->window_start->timestamp - 1,
            'max_date' => $this->source->window_end->timestamp + 1, 'min_id' => 0,
        ];
        $this->travel(2)->hours();
        $this->assertSame([], app(TrackingMessageReader::class)->fetch($this->source, function ($method, $parameters) use ($expected): array {
            $this->assertSame('search', $method);
            $this->assertSame($expected, $parameters);

            return ['_' => 'messages.messagesSlice', 'count' => 0, 'messages' => []];
        }));
    }

    public function test_continuation_uses_offset_without_moving_time_bounds(): void
    {
        $this->source->offset_id = 44;
        config(['telegram_tracking.page_size' => 500]);
        app(TrackingMessageReader::class)->fetch($this->source, function ($method, $parameters): array {
            $this->assertSame('search', $method);
            $this->assertSame(44, $parameters['offset_id']);
            $this->assertSame(100, $parameters['limit']);
            $this->assertSame($this->source->window_start->timestamp - 1, $parameters['min_date']);
            $this->assertSame($this->source->window_end->timestamp + 1, $parameters['max_date']);
            $this->assertArrayNotHasKey('offset_date', $parameters);
            $this->assertArrayNotHasKey('from_id', $parameters);

            return ['messages' => []];
        });
    }

    public function test_pinned_legacy_history_is_not_switched_to_search_mid_page(): void
    {
        $this->source->collection_method = TelegramTrackingSource::HISTORY;
        $this->source->offset_id = 42;
        app(TrackingMessageReader::class)->fetch($this->source, function ($method, $parameters): array {
            $this->assertSame('getHistory', $method);
            $this->assertSame(42, $parameters['offset_id']);
            $this->assertSame(50, $parameters['min_id']);
            $this->assertSame(0, $parameters['offset_date']);
            $this->assertArrayNotHasKey('q', $parameters);

            return ['messages' => []];
        });
    }

    public function test_access_probe_uses_actual_keyword_without_joining_or_fallback(): void
    {
        app(TrackingMessageReader::class)->checkAccess(-100123456789, 'test query', function ($method, $parameters): array {
            $this->assertSame('search', $method);
            $this->assertSame('test query', $parameters['q']);
            $this->assertSame(1, $parameters['limit']);
            $this->assertSame(0, $parameters['floodWaitLimit']);

            return ['messages' => []];
        });
        $calls = 0;
        try {
            app(TrackingMessageReader::class)->fetch($this->source, function ($method) use (&$calls): array {
                $this->assertSame('search', $method);
                $calls++;
                throw new TrackingException('group_unavailable');
            });
            $this->fail('Search denial must propagate.');
        } catch (TrackingException $exception) {
            $this->assertSame('group_unavailable', $exception->reason);
            $this->assertSame(1, $calls);
        }
    }

    public function test_empty_inexact_response_does_not_report_success(): void
    {
        $this->expectException(TrackingException::class);
        $this->expectExceptionMessage('telegram_tracking.errors.search_incomplete');
        app(TrackingMessageReader::class)->fetch($this->source, fn () => ['messages' => [], 'inexact' => true]);
    }

    public static function malformedResponses(): array
    {
        return [[[]], [['_' => 'messages.messagesNotModified', 'count' => 5]], [['messages' => null]], [['messages' => 'invalid']], [['messages' => [null]]]];
    }

    #[DataProvider('malformedResponses')]
    public function test_malformed_response_is_not_treated_as_an_empty_search(array $result): void
    {
        $this->expectException(TrackingException::class);
        $this->expectExceptionMessage('telegram_tracking.errors.collection_failed');
        app(TrackingMessageReader::class)->fetch($this->source, fn () => $result);
    }
}
