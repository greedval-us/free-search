<?php

namespace App\Modules\Telegram\Search\Actions;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use App\Modules\Telegram\DTO\Result\SearchCommentsResultDTO;
use App\Modules\Telegram\Presenters\TelegramCommentPresenter;

class SearchTelegramCommentsAction
{
    public function __construct(
        private readonly TelegramGatewayInterface $gateway,
        private readonly TelegramCommentPresenter $commentPresenter,
    ) {
    }

    public function handle(SearchCommentsQueryDTO $query): SearchCommentsResultDTO
    {
        $commentsPage = $this->gateway->getComments(
            $query->chatUsername,
            $query->postId,
            $query->limit,
            $query->offsetId
        );

        return new SearchCommentsResultDTO(
            $this->commentPresenter->present($commentsPage, $query->limit, $query->offsetId)
        );
    }
}
