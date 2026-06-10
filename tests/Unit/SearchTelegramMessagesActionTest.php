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
    public function test_it_uses_api_from_id_when_author_peer_can_be_resolved(): void
    {
        $gateway = new ResolvedPeerTelegramSearchGateway();
        $action = new SearchTelegramMessagesAction(
            $gateway,
            new TelegramMessagePresenter(),
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
        $this->assertSame('777', $gateway->infoCalls[0]);
        $this->assertSame([
            '_' => 'inputPeerUser',
            'user_id' => 777,
            'access_hash' => 999888777,
        ], $gateway->messageCalls[0]['from_id']);
        $this->assertArrayNotHasKey('authorId', $gateway->messageCalls[0]);
        $this->assertCount(1, $gateway->messageCalls);
    }

    public function test_it_returns_error_when_author_peer_cannot_be_resolved(): void
    {
        $gateway = new UnresolvedPeerTelegramSearchGateway();
        $action = new SearchTelegramMessagesAction(
            $gateway,
            new TelegramMessagePresenter(),
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

        $this->assertFalse($payload['ok']);
        $this->assertSame('Failed to resolve Telegram peer for the specified author ID.', $payload['message']);
        $this->assertSame([], $payload['items']);
        $this->assertSame(2, $payload['pagination']['limit']);
        $this->assertSame(null, $payload['pagination']['nextOffsetId']);
        $this->assertFalse($payload['pagination']['hasMore']);
        $this->assertCount(0, $gateway->messageCalls);
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
