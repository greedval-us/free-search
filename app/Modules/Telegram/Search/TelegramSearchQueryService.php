<?php

namespace App\Modules\Telegram\Search;

use App\Modules\Telegram\Presenters\TelegramCommentPresenter;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\TelegramService;

class TelegramSearchQueryService
{
    public function __construct(
        private readonly TelegramService $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramCommentPresenter $commentPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    public function messages(array $filter, int $limit, int $offsetId, string $chatUsername): array
    {
        $dto = $this->telegramService->getMessages($filter);

        if ($dto === null) {
            return [
                'ok' => false,
                'message' => __('Failed to load messages for the current query.'),
                'items' => [],
                'pagination' => [
                    'limit' => $limit,
                    'offsetId' => $offsetId,
                    'nextOffsetId' => null,
                    'hasMore' => false,
                    'total' => 0,
                ],
            ];
        }

        $items = $this->messagePresenter->presentMessages($dto->messages, $chatUsername);
        $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages);

        return [
            'ok' => true,
            'items' => $items,
            'pagination' => [
                'limit' => $limit,
                'offsetId' => $offsetId,
                'nextOffsetId' => $nextOffsetId,
                'hasMore' => $nextOffsetId !== null && count($items) >= $limit,
                'total' => $dto->count,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function comments(string $chatUsername, int $postId, int $limit, int $offsetId): array
    {
        $commentsPage = $this->telegramService->getComments(
            $chatUsername,
            $postId,
            $limit,
            $offsetId
        );

        return $this->commentPresenter->present($commentsPage, $limit, $offsetId);
    }

    public function media(string $chatUsername, int $messageId): ?array
    {
        if ($chatUsername === '' || $messageId <= 0) {
            return null;
        }

        return $this->telegramService->getMessageMedia($chatUsername, $messageId);
    }
}
