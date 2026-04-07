<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;


class ParticipantsAction extends AbstractTelegramAction
{
    public function execute(array $filter): ?array
    {
        try {
            return $this->executeWithRetry(
                callback: fn () => $this->madeline()->channels->getParticipants($filter),
                context: $filter
            );
        } catch (\Throwable $e) {
            $this->logError($e, $filter);
            return null;
        }
    }
}
