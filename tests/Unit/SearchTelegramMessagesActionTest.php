<?php

namespace Tests\Unit;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\Search\Actions\SearchTelegramMessagesAction;
use Tests\TestCase;

class SearchTelegramMessagesActionTest extends TestCase
{
    public function test_author_and_keyword_are_forwarded_to_telegram_and_use_its_result_count(): void
    {
        $filter = ['peer' => 'channel', 'q' => 'search', 'authorUsername' => 'writer', 'offset_id' => 0];
        $result = $this->search($filter, [
            '_' => 'messages.channelMessages',
            'count' => 9,
            'messages' => [['id' => 205, 'message' => 'search', 'from_id' => 777]],
        ]);

        $this->assertSame([205], array_column($result['items'], 'id'));
        $this->assertSame(9, $result['pagination']['total']);
        $this->assertSame(205, $result['pagination']['nextOffsetId']);
        $this->assertTrue($result['pagination']['hasMore']);
    }

    public function test_a_complete_list_reports_its_count_and_has_no_next_page(): void
    {
        $result = $this->search(['peer' => 'channel', 'q' => 'search'], [
            '_' => 'messages.messages',
            'messages' => [['id' => 205], ['id' => 204]],
        ]);

        $this->assertSame(2, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['hasMore']);
        $this->assertNull($result['pagination']['nextOffsetId']);
    }

    public function test_date_only_history_does_not_report_the_unfiltered_count(): void
    {
        $result = $this->search(['peer' => 'channel', 'max_date' => 1710000400], [
            '_' => 'messages.channelMessages',
            'count' => 3000,
            'messages' => [['id' => 205, 'date' => 1710000205]],
        ]);

        $this->assertSame([205], array_column($result['items'], 'id'));
        $this->assertNull($result['pagination']['total']);
        $this->assertTrue($result['pagination']['hasMore']);
    }

    public function test_a_repeated_source_offset_does_not_create_an_endless_search(): void
    {
        $result = $this->search(['peer' => 'channel', 'offset_id' => 205], [
            '_' => 'messages.channelMessages',
            'messages' => [['id' => 205]],
        ]);

        $this->assertFalse($result['pagination']['hasMore']);
        $this->assertNull($result['pagination']['nextOffsetId']);
    }

    private function search(array $filter, array $response): array
    {
        $gateway = $this->mock(TelegramGatewayInterface::class);
        $gateway->shouldReceive('getMessages')->once()->with($filter)->andReturn(new ChannelMessagesDTO($response));

        return (new SearchTelegramMessagesAction($gateway, new TelegramMessagePresenter))->handle(new SearchMessagesQueryDTO(
            filter: $filter,
            limit: 20,
            offsetId: $filter['offset_id'] ?? 0,
            chatUsername: 'channel',
        ))->toArray();
    }
}
