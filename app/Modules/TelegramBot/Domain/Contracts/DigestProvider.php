<?php

namespace App\Modules\TelegramBot\Domain\Contracts;

use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;

interface DigestProvider
{
    public function allows(BotDelivery $delivery, BotLink $link): bool;

    public function screen(BotDelivery $delivery, BotLink $link): BotScreen;

    public function record(BotDelivery $delivery, string $status): void;
}
