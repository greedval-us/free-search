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
}
