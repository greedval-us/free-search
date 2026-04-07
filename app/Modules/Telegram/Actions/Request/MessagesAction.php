<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;

class MessagesAction extends AbstractTelegramAction
{
    public function execute(array $filter): ?array
    {
        try {
            return $this->executeWithRetry(
                callback: fn () => $this->madeline()->messages->search($filter),
                context: $filter
            );
        } catch (\Throwable $e) {
            $this->logError($e, $filter);
            return null;
        }
    }
}
