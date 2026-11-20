<?php

namespace Tests\Unit;

use App\Exceptions\Public\ExternalServiceRequestException;
use App\Modules\Telegram\Actions\Request\MessagesAction;
use App\Modules\Telegram\DTO\Request\SearchMessagesDTO;
use Tests\TestCase;

class MessagesActionTest extends TestCase
{
    public function test_browsing_uses_history_and_only_the_first_page_uses_the_date_offset(): void
    {
        $action = new TestableMessagesAction;
        $this->assertSame([
            'getHistory',
            ['peer' => ['_' => 'inputPeerChannel', 'channel_id' => 55, 'access_hash' => 99], 'offset_id' => 0,
                'add_offset' => 0, 'limit' => 50, 'max_id' => 0, 'min_id' => 0, 'hash' => [], 'offset_date' => 200],
        ], $action->requestFor(['peer' => 'channel', 'limit' => 50, 'min_date' => 100, 'max_date' => 200]));

        [$method, $params] = $action->requestFor(['peer' => 'channel', 'offset_id' => 55, 'max_date' => 200]);
        $this->assertSame('getHistory', $method);
        $this->assertSame(55, $params['offset_id']);
        $this->assertSame(0, $params['offset_date']);
    }

    public function test_search_passes_the_resolved_public_author_and_date_bounds_to_telegram(): void
    {
        [$method, $params] = (new TestableMessagesAction)->requestFor([
            'peer' => 'channel', 'q' => 'query', 'authorUsername' => 'writer', 'min_date' => 100, 'max_date' => 200,
        ], ['User' => ['_' => 'user', 'id' => 777, 'access_hash' => 888, 'username' => 'writer']]);

        $this->assertSame('search', $method);
        $this->assertSame('query', $params['q']);
        $this->assertSame(['_' => 'inputPeerUser', 'user_id' => 777, 'access_hash' => 888], $params['from_id']);
        $this->assertSame(100, $params['min_date']);
        $this->assertSame(200, $params['max_date']);
    }

    public function test_a_stale_author_username_does_not_execute_an_unfiltered_search(): void
    {
        $this->expectException(ExternalServiceRequestException::class);

        (new TestableMessagesAction)->requestFor(['peer' => 'channel', 'authorUsername' => 'writer'], [
            'User' => ['_' => 'user', 'id' => 777, 'access_hash' => 888, 'username' => 'another'],
        ]);
    }
}

class TestableMessagesAction extends MessagesAction
{
    public function requestFor(array $filter, array $author = []): array
    {
        return $this->loadMessages(
            SearchMessagesDTO::fromArray($filter)->toArray(),
            ['_' => 'inputPeerChannel', 'channel_id' => 55, 'access_hash' => 99],
            static fn (string $method, array $parameters): array => [$method, $parameters],
            static fn (string $username): array => $author,
        );
    }
}
