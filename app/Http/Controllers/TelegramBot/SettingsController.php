<?php

namespace App\Http\Controllers\TelegramBot;

use App\Http\Requests\TelegramBot\ConfirmLinkRequest;
use App\Http\Requests\TelegramBot\UpdatePreferencesRequest;
use App\Modules\TelegramBot\Application\AccountLinkService;
use App\Modules\TelegramBot\Application\BotSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SettingsController
{
    public function __construct(private BotSettings $settings, private AccountLinkService $links) {}

    public function index(Request $request): Response
    {
        return Inertia::render('settings/Telegram', ['telegram' => $this->state($request)]);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $this->state($request)])->header('Cache-Control', 'no-store');
    }

    public function issue(Request $request): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => ['url' => $this->links->issue($request->user(), app()->getLocale()), ...$this->state($request)]])
            ->header('Cache-Control', 'no-store');
    }

    public function confirm(ConfirmLinkRequest $request): JsonResponse
    {
        $this->links->confirm($request->user(), $request->validated('request_id'));

        return $this->status($request);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $this->links->disconnect($request->user());

        return $this->status($request);
    }

    public function preferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $this->settings->update($request->user(), $request->validated());

        return $this->status($request);
    }

    private function state(Request $request): array
    {
        return [
            ...$this->settings->state($request->user()),
            'routes' => [
                'status' => route('telegram-bot.settings.status', absolute: false),
                'issue' => route('telegram-bot.link.issue', absolute: false),
                'confirm' => route('telegram-bot.link.confirm', absolute: false),
                'disconnect' => route('telegram-bot.link.disconnect', absolute: false),
                'preferences' => route('telegram-bot.settings.preferences', absolute: false),
            ],
        ];
    }
}
