<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;



class InfoAction extends AbstractTelegramAction
{
    public function execute(string $id): ?array
    {
        try {
            $response = $this->madeline()->getFullInfo(id: $id);
            return $response;
        } catch (\Exception $e) {
            $this->logError($e, [$id]);
            return ['error' => $e->getMessage()];
        }
    }
}
