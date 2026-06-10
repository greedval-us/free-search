<?php

namespace App\Modules\Telegram\Search\Actions;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Result\SearchMessagesResultDTO;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;

class SearchTelegramMessagesAction
{
    public function __construct(
        private readonly TelegramGatewayInterface $gateway,
        private readonly TelegramMessagePresenter $messagePresenter,
    ) {
    }

    public function handle(SearchMessagesQueryDTO $query): SearchMessagesResultDTO
    {
        $authorId = $this->authorIdFilter($query->filter);

        if ($authorId !== null) {
            return $this->handleAuthorIdSearch($query, $authorId);
        }

        $dto = $this->gateway->getMessages($query->filter);

        if ($dto === null) {
            return new SearchMessagesResultDTO(
                ok: false,
                message: __('Failed to load messages for the current query.'),
                items: [],
                pagination: [
                    'limit' => $query->limit,
                    'offsetId' => $query->offsetId,
                    'nextOffsetId' => null,
                    'hasMore' => false,
                    'total' => 0,
                ],
            );
        }

        $items = $this->messagePresenter->presentMessages($dto->messages, $query->chatUsername);
        $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages);

        return new SearchMessagesResultDTO(
            ok: true,
            items: $items,
            pagination: [
                'limit' => $query->limit,
                'offsetId' => $query->offsetId,
                'nextOffsetId' => $nextOffsetId,
                'hasMore' => $nextOffsetId !== null && count($items) >= $query->limit,
                'total' => $dto->count,
            ],
        );
    }

    /**
     * @param array<string, mixed> $filter
     */
    private function authorIdFilter(array $filter): ?int
    {
        $value = $filter['authorId'] ?? null;

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $authorId = (int) $value;

            return $authorId > 0 ? $authorId : null;
        }

        return null;
    }

    private function handleAuthorIdSearch(SearchMessagesQueryDTO $query, int $authorId): SearchMessagesResultDTO
    {
        $resolvedPeer = $this->resolveAuthorPeer($authorId);

        if ($resolvedPeer === null) {
            return new SearchMessagesResultDTO(
                ok: false,
                message: __('Failed to resolve Telegram peer for the specified author ID.'),
                items: [],
                pagination: [
                    'limit' => $query->limit,
                    'offsetId' => $query->offsetId,
                    'nextOffsetId' => null,
                    'hasMore' => false,
                    'total' => 0,
                ],
            );
        }

        $filter = $query->filter;
        unset($filter['authorId']);
        $filter['from_id'] = $resolvedPeer;

        return $this->handle(new SearchMessagesQueryDTO(
            filter: $filter,
            limit: $query->limit,
            offsetId: $query->offsetId,
            chatUsername: $query->chatUsername,
        ));
    }

    private function resolveAuthorPeer(int $authorId): ?array
    {
        if ($authorId <= 0) {
            return null;
        }

        $info = $this->gateway->getInfo((string) $authorId);

        if ($info === null) {
            return null;
        }

        $user = $this->extractPeerPayload($info->raw, [
            ['User'],
            ['user'],
            ['Full', 'User'],
            ['full', 'user'],
        ]);

        if (is_array($user) && (int) ($user['id'] ?? 0) > 0 && isset($user['access_hash'])) {
            return [
                '_' => 'inputPeerUser',
                'user_id' => (int) $user['id'],
                'access_hash' => (int) $user['access_hash'],
            ];
        }

        $chat = $this->extractPeerPayload($info->raw, [
            ['Chat'],
            ['chat'],
        ]);

        if (! is_array($chat)) {
            return null;
        }

        $chatType = strtolower((string) ($chat['_'] ?? ''));

        if ((int) ($chat['id'] ?? 0) > 0 && isset($chat['access_hash']) && str_contains($chatType, 'channel')) {
            return [
                '_' => 'inputPeerChannel',
                'channel_id' => (int) $chat['id'],
                'access_hash' => (int) $chat['access_hash'],
            ];
        }

        if ((int) ($chat['id'] ?? 0) > 0 && (str_contains($chatType, 'chat') || $chatType === '')) {
            return [
                '_' => 'inputPeerChat',
                'chat_id' => (int) $chat['id'],
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<int, string>> $paths
     */
    private function extractPeerPayload(array $payload, array $paths): ?array
    {
        foreach ($paths as $path) {
            $candidate = $payload;

            foreach ($path as $segment) {
                if (! is_array($candidate) || ! array_key_exists($segment, $candidate)) {
                    $candidate = null;
                    break;
                }

                $candidate = $candidate[$segment];
            }

            if (is_array($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
