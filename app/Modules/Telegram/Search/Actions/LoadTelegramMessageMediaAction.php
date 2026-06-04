<?php

namespace App\Modules\Telegram\Search\Actions;

use App\Modules\Telegram\Core\Contracts\TelegramGatewayInterface;
use App\Modules\Telegram\DTO\Request\SearchMediaQueryDTO;

class LoadTelegramMessageMediaAction
{
    public function __construct(
        private readonly TelegramGatewayInterface $gateway,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function handle(SearchMediaQueryDTO $query): ?array
    {
        if ($query->chatUsername === '' || $query->messageId <= 0) {
            return null;
        }

        return $this->gateway->getMessageMedia($query->chatUsername, $query->messageId);
    }
}
