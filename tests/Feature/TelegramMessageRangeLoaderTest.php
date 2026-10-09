<?php

namespace Tests\Feature;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\Telegram\Analytics\TelegramMessageRangeLoader;
use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\Support\TelegramConfigFactory;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class TelegramMessageRangeLoaderTest extends TestCase
{
    public function test_successful_empty_page_returns_an_empty_range(): void
    {
        $loader = $this->loader([new ChannelMessagesDTO(['messages' => []])]);

        $this->assertSame([], $this->load($loader));
    }

    #[DataProvider('failedPagePositions')]
    public function test_failed_gateway_page_is_never_returned_as_successful_empty_or_partial_analytics(bool $afterMessages): void
    {
        $pages = $afterMessages ? [$this->page([20]), null] : [null];
        $loader = $this->loader($pages);

        try {
            $this->load($loader);
            $this->fail('Failed gateway page must not become a successful report.');
        } catch (ExternalServiceUnavailableException $exception) {
            $this->assertSame(503, $exception->status());
            $this->assertSame('telegram_analytics_load_messages_failed', $exception->errorCode());
            $this->assertSame('errors.api.telegram.load_messages_failed', $exception->translationKey());
        }
    }

    public static function failedPagePositions(): array
    {
        return ['first page' => [false], 'after partial messages' => [true]];
    }

    #[DataProvider('failedPagePositions')]
    public function test_empty_inexact_page_does_not_certify_a_complete_range(bool $afterMessages): void
    {
        $incomplete = new ChannelMessagesDTO(['messages' => [], 'inexact' => true]);
        $loader = $this->loader($afterMessages ? [$this->page([20]), $incomplete] : [$incomplete]);

        try {
            $this->load($loader);
            $this->fail('An inexact empty page cannot certify that all messages were loaded.');
        } catch (ExternalServiceUnavailableException $exception) {
            $this->assertSame(503, $exception->status());
            $this->assertSame('telegram_analytics_load_messages_failed', $exception->errorCode());
        }
    }

    public function test_gateway_bounds_include_both_edge_seconds_and_local_filter_remains_exact(): void
    {
        $from = Carbon::parse('2026-10-08', 'UTC')->startOfDay()->timestamp;
        $to = Carbon::parse('2026-10-08', 'UTC')->endOfDay()->timestamp;
        $parameters = [];
        $gateway = $this->mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('getMessages')->once()->andReturnUsing(function (array $filter) use (&$parameters, $from, $to): ChannelMessagesDTO {
            $parameters = $filter;

            return new ChannelMessagesDTO(['messages' => [
                ['id' => 40, 'date' => $to + 1], ['id' => 30, 'date' => $to],
                ['id' => 20, 'date' => $from], ['id' => 10, 'date' => $from - 1],
            ]]);
        });

        $messages = $this->load($this->makeLoader($gateway, 1, 100));

        $this->assertSame($from - 1, $parameters['min_date']);
        $this->assertSame($to + 1, $parameters['max_date']);
        $this->assertSame([30, 20], array_column($messages, 'id'));
    }

    public function test_gateway_lower_date_bound_is_clamped_at_zero(): void
    {
        $parameters = [];
        $gateway = $this->mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('getMessages')->once()->andReturnUsing(function (array $filter) use (&$parameters): ChannelMessagesDTO {
            $parameters = $filter;

            return $this->page([]);
        });
        $epoch = Carbon::createFromTimestamp(0, 'UTC');
        $loader = $this->makeLoader($gateway, 1, 100);

        $this->assertSame([], $loader->load('publicgroup', $epoch, $epoch));
        $this->assertSame(0, $parameters['min_date']);
        $this->assertSame(1, $parameters['max_date']);
    }

    public function test_short_search_pages_continue_until_an_empty_page_and_duplicates_are_removed(): void
    {
        $offsets = [];
        $gateway = $this->mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('getMessages')->times(3)->andReturnUsing(function (array $filter) use (&$offsets): ChannelMessagesDTO {
            $offsets[] = $filter['offset_id'];
            $this->assertSame('keyword', $filter['q']);

            return match ($filter['offset_id']) {
                0 => $this->page([30]),
                30 => $this->page([30, 20]),
                20 => $this->page([]),
            };
        });
        $loader = $this->makeLoader($gateway, 4, 100);

        $messages = $this->load($loader, 'keyword');

        $this->assertSame([30, 20], array_column($messages, 'id'));
        $this->assertSame([0, 30, 20], $offsets);
    }

    public function test_exhausting_page_cap_without_observed_end_does_not_return_partial_analytics(): void
    {
        $loader = $this->loader([$this->page([30]), $this->page([20])], 2);

        try {
            $this->load($loader);
            $this->fail('Collection cap must not produce a successful partial report.');
        } catch (ExternalServiceRequestException $exception) {
            $this->assertSame(422, $exception->status());
            $this->assertSame('telegram_analytics_collection_limit', $exception->errorCode());
        }
    }

    public function test_empty_last_page_at_cap_completes_the_range(): void
    {
        $loader = $this->loader([$this->page([30]), $this->page([])], 2);

        $this->assertSame([30], array_column($this->load($loader), 'id'));
    }

    public function test_reaching_oldest_date_completes_without_fetching_more_pages(): void
    {
        $page = new ChannelMessagesDTO(['messages' => [
            ['id' => 30, 'date' => Carbon::parse('2026-10-08 12:00:00', 'UTC')->timestamp],
            ['id' => 20, 'date' => Carbon::parse('2026-10-07 23:59:59', 'UTC')->timestamp],
        ]]);
        $loader = $this->loader([$page], 1);

        $this->assertSame([30], array_column($this->load($loader), 'id'));
    }

    #[DataProvider('stalledPages')]
    public function test_non_advancing_or_invalid_cursor_fails_instead_of_returning_partial_messages(array $ids): void
    {
        $loader = $this->loader([$this->page([30]), $this->page($ids)]);

        try {
            $this->load($loader);
            $this->fail('A stalled cursor cannot prove that the range is complete.');
        } catch (ExternalServiceRequestException $exception) {
            $this->assertSame(422, $exception->status());
            $this->assertSame('telegram_analytics_pagination_stalled', $exception->errorCode());
        }
    }

    public static function stalledPages(): array
    {
        return ['repeated offset' => [[30]], 'offset moves forward' => [[40]], 'invalid offset' => [[0]]];
    }

    /** @param list<ChannelMessagesDTO|null> $pages */
    private function loader(array $pages, int $maxPages = 4): TelegramMessageRangeLoader
    {
        $gateway = $this->mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('getMessages')->times(count($pages))->andReturn(...$pages);

        return $this->makeLoader($gateway, $maxPages, 1);
    }

    private function makeLoader(TelegramGatewayInterface $gateway, int $maxPages, int $pageLimit): TelegramMessageRangeLoader
    {
        $config = (new TelegramConfigFactory)->make(['analytics' => ['fetch' => ['max_pages' => $maxPages, 'page_limit' => $pageLimit]]], 'UTC');

        return new TelegramMessageRangeLoader($gateway, new TelegramMessagePresenter, $config);
    }

    /** @return array<int, object> */
    private function load(TelegramMessageRangeLoader $loader, ?string $keyword = null): array
    {
        return $loader->load('publicgroup', Carbon::parse('2026-10-08', 'UTC')->startOfDay(), Carbon::parse('2026-10-08', 'UTC')->endOfDay(), $keyword);
    }

    /** @param list<int> $ids */
    private function page(array $ids): ChannelMessagesDTO
    {
        return new ChannelMessagesDTO(['messages' => array_map(fn (int $id): array => ['id' => $id,
            'date' => Carbon::parse('2026-10-08 12:00:00', 'UTC')->timestamp], $ids)]);
    }
}
