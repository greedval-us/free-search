<?php

namespace App\Http\Middleware\TelegramBot;

use App\Modules\TelegramBot\Support\BotConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyBotWebhook
{
    public function __construct(private BotConfig $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->config->active(), 404);
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        abort_unless(is_string($secret) && hash_equals($this->config->secret(), $secret), 403);

        return $next($request);
    }
}
