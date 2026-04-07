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
            $message = strtoupper($e->getMessage());

            if (str_contains($message, 'CHAT_ADMIN_REQUIRED')) {
                $this->logWarning(
                    message: 'Participants list is not accessible without admin rights for this chat/channel',
                    data: $filter
                );
                return null;
            }

            $this->logError($e, $filter);
            return null;
        }
    }
}
