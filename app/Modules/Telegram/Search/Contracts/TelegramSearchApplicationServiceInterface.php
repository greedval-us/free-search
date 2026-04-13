<?php

namespace App\Modules\Telegram\Search\Contracts;

use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMediaQueryDTO;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use App\Modules\Telegram\DTO\Result\SearchCommentsResultDTO;
use App\Modules\Telegram\DTO\Result\SearchMessagesResultDTO;

interface TelegramSearchApplicationServiceInterface
{
    public function messages(SearchMessagesQueryDTO $query): SearchMessagesResultDTO;

    public function comments(SearchCommentsQueryDTO $query): SearchCommentsResultDTO;

    /**
     * @return array<string, mixed>|null
     */
    public function media(SearchMediaQueryDTO $query): ?array;
}
