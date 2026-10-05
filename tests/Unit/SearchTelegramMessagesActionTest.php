<?php

namespace Tests\Unit;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Response\Info\ChannelInfoDTO;
use App\Modules\Telegram\DTO\Response\Messages\ChannelMessagesDTO;
use App\Modules\Telegram\DTO\Response\Participants\ChannelParticipantsDTO;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\Search\Actions\SearchTelegramMessagesAction;
use Tests\TestCase;

class SearchTelegramMessagesActionTest extends TestCase
{
    public function test_it_filters_public_messages_by_author_without_resolving_a_private_profile(): void
    {
        $gateway = new ResolvedPeerTelegramSearchGateway;
        $action = new SearchTelegramMessagesAction(
            $gateway,
            new TelegramMessagePresenter,
        );

        $result = $action->handle(new SearchMessagesQueryDTO(
            filter: [
                'peer' => '@channel',
                'limit' => 2,
                'offset_id' => 0,
                'authorId' => 777,
            ],
            limit: 2,
            offsetId: 0,
            chatUsername: 'channel',
        ));

        $payload = $result->toArray();

        $this->assertTrue($payload['ok']);
        $this->assertCount(1, $payload['items']);
        $this->assertSame(777, $payload['items'][0]['authorId']);
        $this->assertSame([], $gateway->infoCalls);
        $this->assertArrayNotHasKey('from_id', $gateway->messageCalls[0]);
        $this->assertArrayNotHasKey('authorId', $gateway->messageCalls[0]);
        $this->assertCount(1, $gateway->messageCalls);
        $this->assertTrue($payload['pagination']['hasMore']);
    }

    public function test_it_keeps_the_source_cursor_and_pagination_after_filtering_a_page(): void
    {
        $gateway = new UnresolvedPeerTelegramSearchGateway;
        $action = new SearchTelegramMessagesAction(
            $gateway,
            new TelegramMessagePresenter,
        );

        $result = $action->handle(new SearchMessagesQueryDTO(
            filter: [
                'peer' => '@channel',
                'limit' => 2,
                'offset_id' => 0,
                'authorId' => 777,
            ],
            limit: 2,
            offsetId: 0,
            chatUsername: 'channel',
        ));

        $payload = $result->toArray();

        $this->assertTrue($payload['ok']);
        $this->assertCount(1, $payload['items']);
        $this->assertSame(104, $payload['items'][0]['id']);
        $this->assertSame(2, $payload['pagination']['limit']);
        $this->assertSame(101, $payload['pagination']['nextOffsetId']);
        $this->assertTrue($payload['pagination']['hasMore']);
        $this->assertCount(1, $gateway->messageCalls);
        $this->assertArrayNotHasKey('from_id', $gateway->messageCalls[0]);
    }

    public function test_an_empty_author_match_page_still_allows_the_next_source_page(): void
    {
        $gateway = new UnresolvedPeerTelegramSearchGateway;
        $action = new SearchTelegramMessagesAction($gateway, new TelegramMessagePresenter);

        $result = $action->handle(new SearchMessagesQueryDTO(
            filter: ['peer' => '@channel', 'limit' => 2, 'authorId' => 999],
            limit: 2,
            offsetId: 0,
            chatUsername: 'channel',
        ))->toArray();

        $this->assertSame([], $result['items']);
        $this->assertSame(101, $result['pagination']['nextOffsetId']);
        $this->assertTrue($result['pagination']['hasMore']);
    }

    public function test_a_repeated_source_offset_does_not_create_an_endless_search(): void
    {
        $gateway = new ResolvedPeerTelegramSearchGateway;
        $action = new SearchTelegramMessagesAction($gateway, new TelegramMessagePresenter);

        $result = $action->handle(new SearchMessagesQueryDTO(
            filter: ['peer' => '@channel', 'offset_id' => 205],
            limit: 20,
            offsetId: 205,
            chatUsername: 'channel',
        ))->toArray();

        $this->assertFalse($result['pagination']['hasMore']);
        $this->assertNull($result['pagination']['nextOffsetId']);
    }

    public function test_author_usernames_match_only_public_page_metadata_and_active_aliases(): void
    {
        $gateway = new ResolvedPeerTelegramSearchGateway;
        $action = new SearchTelegramMessagesAction($gateway, new TelegramMessagePresenter);

        foreach (['writer', 'alias'] as $username) {
            $result = $action->handle(new SearchMessagesQueryDTO(
                filter: ['peer' => '@channel', 'authorUsername' => $username],
                limit: 20, offsetId: 0, chatUsername: 'channel',
            ))->toArray();
            $this->assertSame(777, $result['items'][0]['authorId']);
        }
        $result = $action->handle(new SearchMessagesQueryDTO(
            filter: ['peer' => '@channel', 'authorUsername' => 'former'],
            limit: 20, offsetId: 0, chatUsername: 'channel',
        ))->toArray();

        $this->assertSame([], $gateway->infoCalls);
        $this->assertSame([], $result['items']);
        $this->assertTrue($result['pagination']['hasMore']);
    }
}

class UnresolvedPeerTelegramSearchGateway implements TelegramGatewayInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $messageCalls = [];

    public function getInfo(string $id): ?ChannelInfoDTO
    {
        return null;
    }

    public function getMessages(array $filter): ?ChannelMessagesDTO
    {
        $this->messageCalls[] = $filter;

        $offsetId = (int) ($filter['offset_id'] ?? 0);

        if ($offsetId === 0) {
            return new ChannelMessagesDTO([
                '_' => 'messages.channelMessages',
                'count' => 4,
                'messages' => [
                    [
                        'id' => 104,
                        'date' => 1710000104,
                        'message' => 'match-1',
                        'from_id' => ['user_id' => 777],
                        'peer_id' => ['channel_id' => 55],
                    ],
                    [
                        'id' => 103,
                        'date' => 1710000103,
                        'message' => 'skip',
                        'from_id' => ['user_id' => 888],
                        'peer_id' => ['channel_id' => 55],
                    ],
                    [
                        'id' => 101,
                        'date' => 1710000101,
                        'message' => 'skip-2',
                        'from_id' => ['user_id' => 555],
                        'peer_id' => ['channel_id' => 55],
                    ],
                ],
            ]);
        }

        return new ChannelMessagesDTO([
            '_' => 'messages.channelMessages',
            'count' => 4,
            'messages' => [
                [
                    'id' => 102,
                    'date' => 1710000102,
                    'message' => 'match-2',
                    'from_id' => ['user_id' => 777],
                    'peer_id' => ['channel_id' => 55],
                ],
                [
                    'id' => 100,
                    'date' => 1710000100,
                    'message' => 'older-skip',
                    'from_id' => ['user_id' => 999],
                    'peer_id' => ['channel_id' => 55],
                ],
            ],
        ]);
    }

    public function getParticipants(array $filter): ?ChannelParticipantsDTO
    {
        return null;
    }

    public function getComments(string $channel, int $postId, int $limit = 20, int $offsetId = 0): array
    {
        return [];
    }

    public function getMessageMedia(string $channel, int $messageId): ?array
    {
        return null;
    }

    public function downloadMediaToFile(array $media, string $path): string
    {
        return $path;
    }
}

class ResolvedPeerTelegramSearchGateway implements TelegramGatewayInterface
{
    /**
     * @var array<int, string>
     */
    public array $infoCalls = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $messageCalls = [];

    public function getInfo(string $id): ?ChannelInfoDTO
    {
        $this->infoCalls[] = $id;

        return new ChannelInfoDTO([
            'id' => 777,
            'type' => 'user',
            'User' => [
                '_' => 'user',
                'id' => 777,
                'access_hash' => 999888777,
            ],
        ]);
    }

    public function getMessages(array $filter): ?ChannelMessagesDTO
    {
        $this->messageCalls[] = $filter;

        return new ChannelMessagesDTO([
            '_' => 'messages.channelMessages',
            'count' => 1,
            'users' => [['id' => 777, 'username' => 'writer', 'usernames' => [
                ['username' => 'alias', 'active' => true], ['username' => 'former', 'active' => false],
            ]]],
            'messages' => [
                [
                    'id' => 205,
                    'date' => 1710000205,
                    'message' => 'api-match',
                    'from_id' => ['user_id' => 777],
                    'peer_id' => ['channel_id' => 55],
                ],
            ],
        ]);
    }

    public function getParticipants(array $filter): ?ChannelParticipantsDTO
    {
        return null;
    }

    public function getComments(string $channel, int $postId, int $limit = 20, int $offsetId = 0): array
    {
        return [];
    }

    public function getMessageMedia(string $channel, int $messageId): ?array
    {
        return null;
    }

    public function downloadMediaToFile(array $media, string $path): string
    {
        return $path;
    }
}
