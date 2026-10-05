<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;

class MessagesAction extends AbstractTelegramAction
{
    public function execute(array $filter): ?array
    {
        $client = $this->madeline();

        try {
            $source = $this->publicSource($client, (string) ($filter['peer'] ?? ''));
            if ($source === null || filled($filter['from_id'] ?? null) || filled($filter['saved_peer_id'] ?? null)) {
                return null;
            }
            $filter['peer'] = $source['peer'];

            return $this->executeWithRetry(
                callback: fn () => $client->messages->search($filter),
                context: $filter
            );
        } catch (\Throwable $e) {
            $this->logError($e, $filter);

            return null;
        }
    }
}
