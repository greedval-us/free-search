<?php

namespace App\Modules\Telegram\Search\Actions;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchMessagesDTO;
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
        $dto = $this->gateway->getMessages($query->filter);

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
        if ($dto->_ === 'messages.messages' || ($nextOffsetId !== null && $query->offsetId > 0 && $nextOffsetId >= $query->offsetId)) {
            $nextOffsetId = null;
        }
        $hasBoundedHistory = SearchMessagesDTO::usesHistory($query->filter)
            && (! empty($query->filter['min_date']) || ! empty($query->filter['max_date']));

        return new SearchMessagesResultDTO(
            ok: true,
            items: $items,
            pagination: [
                'limit' => $query->limit,
                'offsetId' => $query->offsetId,
                'nextOffsetId' => $nextOffsetId,
                'hasMore' => $nextOffsetId !== null,
                'total' => $hasBoundedHistory ? null : $dto->count,
            ],
        );
    }
}
