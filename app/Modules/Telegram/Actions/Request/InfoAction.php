<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;



class InfoAction extends AbstractTelegramAction
{
    public function execute(string $id): ?array
    {
        try {
            return $this->executeWithRetry(
                callback: fn () => $this->madeline()->getFullInfo(id: $id),
                context: ['id' => $id]
            );
        } catch (\Throwable $e) {
            $this->logError($e, ['id' => $id]);
            return null;
        }
    }
}
