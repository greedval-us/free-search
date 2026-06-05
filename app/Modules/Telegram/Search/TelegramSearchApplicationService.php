<?php

namespace App\Modules\Telegram\Search;

use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMediaQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Result\SearchCommentsResultDTO;
use App\Modules\Telegram\DTO\Result\SearchMessagesResultDTO;
use App\Modules\Telegram\Search\Contracts\TelegramSearchApplicationServiceInterface;
use App\Modules\Telegram\Search\Actions\LoadTelegramMessageMediaAction;
use App\Modules\Telegram\Search\Actions\SearchTelegramCommentsAction;
use App\Modules\Telegram\Search\Actions\SearchTelegramMessagesAction;

class TelegramSearchApplicationService implements TelegramSearchApplicationServiceInterface
{
    public function __construct(
        private readonly SearchTelegramMessagesAction $searchMessagesAction,
        private readonly SearchTelegramCommentsAction $searchCommentsAction,
        private readonly LoadTelegramMessageMediaAction $loadMessageMediaAction,
    ) {
    }

    public function messages(SearchMessagesQueryDTO $query): SearchMessagesResultDTO
    {
        return $this->searchMessagesAction->handle($query);
    }

    public function comments(SearchCommentsQueryDTO $query): SearchCommentsResultDTO
    {
        return $this->searchCommentsAction->handle($query);
    }

    public function media(SearchMediaQueryDTO $query): ?array
    {
        return $this->loadMessageMediaAction->handle($query);
    }
}

