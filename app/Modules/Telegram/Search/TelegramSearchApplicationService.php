<?php

namespace App\Modules\Telegram\Search;

use App\Modules\Telegram\Presenters\TelegramCommentPresenter;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMediaQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\TelegramService;

class TelegramSearchApplicationService
{
    public function __construct(
        private readonly TelegramService $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramCommentPresenter $commentPresenter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(SearchMessagesQueryDTO $query): array
    {
        $dto = $this->telegramService->getMessages($query->filter);

        if ($dto === null) {
            return [
                'ok' => false,
                'message' => __('Failed to load messages for the current query.'),
                'items' => [],
                'pagination' => [
                    'limit' => $query->limit,
                    'offsetId' => $query->offsetId,
                    'nextOffsetId' => null,
                    'hasMore' => false,
                    'total' => 0,
                ],
            ];
        }

        $items = $this->messagePresenter->presentMessages($dto->messages, $query->chatUsername);
        $nextOffsetId = $this->messagePresenter->resolveNextOffsetId($dto->messages);

        return [
            'ok' => true,
            'items' => $items,
            'pagination' => [
                'limit' => $query->limit,
                'offsetId' => $query->offsetId,
                'nextOffsetId' => $nextOffsetId,
                'hasMore' => $nextOffsetId !== null && count($items) >= $query->limit,
                'total' => $dto->count,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function comments(SearchCommentsQueryDTO $query): array
    {
        $commentsPage = $this->telegramService->getComments(
            $query->chatUsername,
            $query->postId,
            $query->limit,
            $query->offsetId
        );

        return $this->commentPresenter->present($commentsPage, $query->limit, $query->offsetId);
    }

    public function media(SearchMediaQueryDTO $query): ?array
    {
        if ($query->chatUsername === '' || $query->messageId <= 0) {
            return null;
        }

        return $this->telegramService->getMessageMedia($query->chatUsername, $query->messageId);
    }
}


