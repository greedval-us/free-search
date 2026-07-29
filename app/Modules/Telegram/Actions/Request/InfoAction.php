<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;



class InfoAction extends AbstractTelegramAction
{
    public function execute(string $id): ?array
    {
        $client = $this->madeline();

        try {
            return $this->executeWithRetry(
                callback: fn () => $client->getFullInfo(id: $id),
                context: ['id' => $id]
            );
        } catch (\Throwable $e) {
            $this->logError($e, ['id' => $id]);
            return null;
        }
    }
}
