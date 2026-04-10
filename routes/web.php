<?php

use App\Http\Controllers\Telegram\TelegramAnalyticsController;
use App\Http\Controllers\TelegramSearchController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('telegram', 'Telegram')->name('telegram');
    Route::get('telegram/search/messages', [TelegramSearchController::class, 'messages'])->name('telegram.search.messages');
    Route::get('telegram/search/comments', [TelegramSearchController::class, 'comments'])->name('telegram.search.comments');
    Route::get('telegram/media/{chatUsername}/{messageId}', [TelegramSearchController::class, 'media'])->name('telegram.media');
    Route::get('telegram/analytics/summary', [TelegramAnalyticsController::class, 'summary'])->name('telegram.analytics.summary');
    Route::get('telegram/analytics/report', [TelegramAnalyticsController::class, 'report'])->name('telegram.analytics.report');
});

require __DIR__.'/settings.php';
