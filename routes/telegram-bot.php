<?php

use App\Http\Controllers\TelegramBot\SettingsController;
use App\Http\Controllers\TelegramBot\WebhookController;
use App\Http\Middleware\TelegramBot\VerifyBotWebhook;
use Illuminate\Support\Facades\Route;

// Stateless webhook: authenticated by Telegram's secret header, not a web session.
Route::post('integrations/telegram-bot/webhook', WebhookController::class)
    ->middleware(VerifyBotWebhook::class)->name('telegram-bot.webhook');

Route::middleware(['web', 'auth', 'verified'])->prefix('settings/telegram')->name('telegram-bot.')->group(function (): void {
    Route::get('/', [SettingsController::class, 'index'])->name('settings');
    Route::get('status', [SettingsController::class, 'status'])->middleware('throttle:telegram-bot-status')->name('settings.status');
    Route::post('link', [SettingsController::class, 'issue'])->middleware('throttle:telegram-bot-link')->name('link.issue');
    Route::post('confirm', [SettingsController::class, 'confirm'])->middleware('throttle:telegram-bot-link')->name('link.confirm');
    Route::delete('link', [SettingsController::class, 'disconnect'])->middleware('throttle:telegram-bot-link')->name('link.disconnect');
    Route::patch('preferences', [SettingsController::class, 'preferences'])->middleware('throttle:telegram-bot-preferences')->name('settings.preferences');
});
