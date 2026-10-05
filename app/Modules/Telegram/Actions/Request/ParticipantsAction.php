<?php

namespace App\Modules\Telegram\Actions\Request;

use App\Modules\Telegram\Actions\AbstractTelegramAction;

class ParticipantsAction extends AbstractTelegramAction
{
    public function execute(array $filter): ?array
    {
        // Shared session membership and administrator privileges are not a public OSINT source.
        return null;
    }
}
