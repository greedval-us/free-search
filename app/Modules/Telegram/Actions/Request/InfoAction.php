<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;
use danog\DialogId\DialogId;

class InfoAction extends AbstractTelegramAction
{
    public function execute(string $id): ?array
    {
        $client = $this->madeline();

        try {
            $source = $this->publicSource($client, $id);
            if ($source === null) {
                return null;
            }

            return $this->executeWithRetry(
                callback: fn () => $client->getFullInfo(id: DialogId::fromSupergroupOrChannelId($source['id'])),
                context: ['id' => $id]
            );
        } catch (\Throwable $e) {
            $this->logError($e, ['id' => $id]);

            return null;
        }
    }
}
