<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;


class ParticipantsAction extends AbstractTelegramAction
{
    public function execute(array $filter): ?array
    {
        try {
            $response = $this->madeline()->channels->getParticipants($filter);
            return $response;
        } catch (\Exception $e) {
            $this->logError($e, $filter);
            return ['error' => $e->getMessage()];
        }
    }
}
