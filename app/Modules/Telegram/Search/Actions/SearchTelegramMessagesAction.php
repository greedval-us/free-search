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
    ) {}

    public function handle(SearchMessagesQueryDTO $query): SearchMessagesResultDTO
    {
        $authorId = $this->authorIdFilter($query->filter);

        $filter = $query->filter;
        unset($filter['authorId'], $filter['authorUsername']);
        $dto = $this->gateway->getMessages($filter);

        if ($dto === null) {
            return new SearchMessagesResultDTO(
                ok: false,
                message: __('errors.api.telegram.load_messages_failed'),
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
        if ($nextOffsetId !== null && $query->offsetId > 0 && $nextOffsetId >= $query->offsetId) {
            $nextOffsetId = null;
        }
        $authorUsername = $query->filter['authorUsername'] ?? null;
        if ($authorUsername !== null) {
            $authorId = $this->publicAuthorId($dto->raw, (string) $authorUsername);
        }
        if ($authorId !== null || $authorUsername !== null) {
            $items = array_values(array_filter($items, static fn (array $item): bool => $authorId !== null && $item['authorId'] === $authorId));
        }

        return new SearchMessagesResultDTO(
            ok: true,
            items: $items,
            pagination: [
                'limit' => $query->limit,
                'offsetId' => $query->offsetId,
                'nextOffsetId' => $nextOffsetId,
                'hasMore' => $nextOffsetId !== null && $dto->messages !== [],
                'total' => $dto->count,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filter
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

    private function publicAuthorId(array $payload, string $username): ?int
    {
        foreach (array_merge($payload['users'] ?? [], $payload['chats'] ?? []) as $author) {
            if (is_array($author) && strcasecmp((string) ($author['username'] ?? ''), $username) === 0) {
                return (int) ($author['id'] ?? 0) ?: null;
            }
            foreach (is_array($author) ? ($author['usernames'] ?? []) : [] as $alias) {
                if (is_array($alias) && ($alias['active'] ?? false)
                    && strcasecmp((string) ($alias['username'] ?? ''), $username) === 0) {
                    return (int) ($author['id'] ?? 0) ?: null;
                }
            }
        }

        return null;
    }
}
