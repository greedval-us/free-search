<?php

namespace Tests\Feature;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\Parser\TelegramParserCollector;
use App\Modules\Telegram\Presenters\TelegramCommentPresenter;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use RuntimeException;
use Tests\TestCase;

class TelegramParserCollectorTest extends TestCase
{
    public function test_short_message_pages_continue_until_empty_and_overlap_is_deduplicated(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->expects($this->once())->method('getInfo')->willReturn(new ChannelInfoDTO(['chat' => ['broadcast' => true]]));
        $offsets = [];
        $gateway->expects($this->exactly(3))->method('getMessages')->willReturnCallback(function (array $filter) use (&$offsets): ChannelMessagesDTO {
            $offsets[] = $filter['offset_id'];

            return match ($filter['offset_id']) {
                0 => $this->page([['id' => 100, 'date' => 200], ['id' => 90, 'date' => 190]]),
                90 => $this->page([['id' => 90, 'date' => 190], ['id' => 80, 'date' => 180]]),
                80 => $this->page([]),
            };
        });
        $collector = $this->collector($gateway);
        $run = $this->initialRun();

        $run = $collector->advance($run);
        $this->assertSame('messages', $run['stage']);
        $run = $collector->advance($run);
        $run = $collector->advance($run);
        $run = $collector->advance($run);

        $this->assertSame([0, 90, 80], $offsets);
        $this->assertSame('completed', $run['status']);
        $this->assertSame([100, 90, 80], array_column($run['result']['messages'], 'id'));
        $this->assertSame(3, $run['result']['messagesCount']);
    }

    public function test_keyword_search_passes_dates_and_filters_out_of_range_results(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->method('getInfo')->willReturn(new ChannelInfoDTO(['chat' => ['broadcast' => false]]));
        $gateway->expects($this->once())->method('getMessages')->with($this->callback(
            static fn (array $filter): bool => $filter['q'] === 'osint' && $filter['min_date'] === 100 && $filter['max_date'] === 200,
        ))->willReturn($this->page([
            ['id' => 4, 'date' => 250], ['id' => 3, 'date' => 200], ['id' => 2, 'date' => 100], ['id' => 1, 'date' => 50],
        ]));
        $run = $this->initialRun();
        $run['context']['keyword'] = 'osint';
        $run['context']['range'] = ['minTimestamp' => 100, 'maxTimestamp' => 200];

        $after = $this->collector($gateway)->advance($run);

        $this->assertSame([3, 2], array_column($after['data']['messages'], 'id'));
    }

    public function test_unknown_source_info_cannot_skip_channel_comments_as_success(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->method('getInfo')->willReturn(null);
        $gateway->expects($this->never())->method('getMessages');

        $this->expectException(RuntimeException::class);

        $this->collector($gateway)->advance($this->initialRun());
    }

    public function test_failed_comment_page_is_not_treated_as_empty_completion(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->expects($this->once())->method('getComments')->willReturn(['ok' => false, 'items' => [], 'hasMore' => false]);
        $run = $this->commentRun();

        $this->expectException(RuntimeException::class);

        $this->collector($gateway)->advance($run);
    }

    public function test_repeated_message_offset_is_rejected(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->method('getInfo')->willReturn(new ChannelInfoDTO(['chat' => ['broadcast' => false]]));
        $gateway->expects($this->once())->method('getMessages')->willReturn($this->page([['id' => 90, 'date' => 100]]));
        $run = $this->initialRun();
        $run['cursor']['messagesOffsetId'] = 90;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pagination did not advance');

        $this->collector($gateway)->advance($run);
    }

    public function test_repeated_comment_offset_is_rejected(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->expects($this->once())->method('getComments')->willReturn(['ok' => true, 'items' => [['id' => 90]], 'hasMore' => true, 'nextOffsetId' => 90]);
        $run = $this->commentRun();
        $run['cursor']['commentOffsetId'] = 90;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pagination did not advance');

        $this->collector($gateway)->advance($run);
    }

    public function test_comment_pages_preserve_cursor_and_deduplicate_by_post(): void
    {
        $gateway = $this->createMock(TelegramGatewayInterface::class);
        $gateway->expects($this->exactly(3))->method('getComments')->willReturnOnConsecutiveCalls(
            ['ok' => true, 'items' => [['id' => 90]], 'hasMore' => true, 'nextOffsetId' => 90],
            ['ok' => true, 'items' => [['id' => 90], ['id' => 80]], 'hasMore' => false],
            ['ok' => true, 'items' => [['id' => 90]], 'hasMore' => false],
        );
        $run = $this->commentRun();
        $run['cursor']['commentPostIds'] = [100, 101];
        $collector = $this->collector($gateway);

        $run = $collector->advance($run);
        $this->assertSame(90, $run['cursor']['commentOffsetId']);
        $run = $collector->advance($run);
        $this->assertSame(1, $run['cursor']['commentPostIndex']);
        $run = $collector->advance($run);

        $this->assertSame('finishing', $run['stage']);
        $this->assertSame(3, $run['stats']['processedComments']);
        $this->assertSame([100, 100, 101], array_column($run['data']['commentsIndex'], 'postId'));
    }

    private function collector(TelegramGatewayInterface $gateway): TelegramParserCollector
    {
        return new TelegramParserCollector($gateway, new TelegramMessagePresenter, new TelegramCommentPresenter);
    }

    private function page(array $messages): ChannelMessagesDTO
    {
        return new ChannelMessagesDTO(['_' => 'messages.messagesSlice', 'count' => 3, 'messages' => $messages]);
    }

    private function initialRun(): array
    {
        return ['status' => 'running', 'stage' => 'messages', 'context' => ['chatUsername' => 'example'], 'cursor' => [], 'data' => [], 'stats' => []];
    }

    private function commentRun(): array
    {
        return [...$this->initialRun(), 'stage' => 'comments', 'cursor' => ['commentPostIds' => [100], 'commentPostIndex' => 0, 'commentOffsetId' => 0]];
    }
}
