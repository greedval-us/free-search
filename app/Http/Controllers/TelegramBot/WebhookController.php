<?php

namespace App\Http\Controllers\TelegramBot;

use App\Modules\TelegramBot\Domain\DTO\BotUpdate;
use App\Modules\TelegramBot\Jobs\ProcessBotUpdate;
use App\Modules\TelegramBot\Support\BotConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;

final readonly class WebhookController
{
    public function __invoke(Request $request, BotConfig $config): Response
    {
        $update = BotUpdate::fromArray($request->json()->all());
        if ($update !== null) {
            Bus::dispatch(new ProcessBotUpdate($config->botId(), $update));
        }

        return response()->noContent();
    }
}
