<?php

namespace App\Modules\Telegram\Search;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\Presenters\TelegramCommentPresenter;
use App\Modules\Telegram\Presenters\TelegramMessagePresenter;
use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMediaQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Result\SearchCommentsResultDTO;
use App\Modules\Telegram\DTO\Result\SearchMessagesResultDTO;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;

class TelegramSearchApplicationService implements TelegramSearchApplicationServiceInterface
{
    public function __construct(
        private readonly TelegramGatewayInterface $telegramService,
        private readonly TelegramMessagePresenter $messagePresenter,
        private readonly TelegramCommentPresenter $commentPresenter,
    ) {
    }

    public function messages(SearchMessagesQueryDTO $query): SearchMessagesResultDTO
    {
        $dto = $this->telegramService->getMessages($query->filter);

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

    public function comments(SearchCommentsQueryDTO $query): SearchCommentsResultDTO
    {
        $commentsPage = $this->telegramService->getComments(
            $query->chatUsername,
            $query->postId,
            $query->limit,
            $query->offsetId
        );

        return new SearchCommentsResultDTO(
            $this->commentPresenter->present($commentsPage, $query->limit, $query->offsetId)
        );
    }

    public function media(SearchMediaQueryDTO $query): ?array
    {
        if ($query->chatUsername === '' || $query->messageId <= 0) {
            return null;
        }

        return $this->telegramService->getMessageMedia($query->chatUsername, $query->messageId);
    }
}


