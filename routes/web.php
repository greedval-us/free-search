<?php

use App\Http\Controllers\Gdelt\GdeltSearchController;
use App\Http\Controllers\Telegram\TelegramAnalyticsController;
use App\Http\Controllers\Telegram\TelegramParserController;
use App\Http\Controllers\Telegram\TelegramSearchController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('telegram', 'Telegram')->name('telegram');
    Route::get('gdelt/search/articles', [GdeltSearchController::class, 'articles'])
        ->middleware('throttle:30,1')
        ->name('gdelt.search.articles');
    Route::get('telegram/search/messages', [TelegramSearchController::class, 'messages'])
        ->middleware('throttle:90,1')
        ->name('telegram.search.messages');
    Route::get('telegram/search/comments', [TelegramSearchController::class, 'comments'])
        ->middleware('throttle:90,1')
        ->name('telegram.search.comments');
    Route::get('telegram/media/{chatUsername}/{messageId}', [TelegramSearchController::class, 'media'])
        ->middleware('throttle:120,1')
        ->name('telegram.media');
    Route::get('telegram/analytics/summary', [TelegramAnalyticsController::class, 'summary'])
        ->middleware('throttle:20,1')
        ->name('telegram.analytics.summary');
    Route::get('telegram/analytics/report', [TelegramAnalyticsController::class, 'report'])
        ->middleware('throttle:10,1')
        ->name('telegram.analytics.report');
    Route::post('telegram/parser/start', [TelegramParserController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('telegram.parser.start');
    Route::get('telegram/parser/status/{runId}', [TelegramParserController::class, 'status'])
        ->middleware('throttle:40,1')
        ->name('telegram.parser.status');
    Route::post('telegram/parser/stop/{runId}', [TelegramParserController::class, 'stop'])
        ->middleware('throttle:20,1')
        ->name('telegram.parser.stop');
    Route::get('telegram/parser/download-excel/{runId}', [TelegramParserController::class, 'downloadExcel'])
        ->middleware('throttle:10,1')
        ->name('telegram.parser.download-excel');
    Route::get('telegram/parser/download-json/{runId}', [TelegramParserController::class, 'downloadJson'])
        ->middleware('throttle:10,1')
        ->name('telegram.parser.download-json');
});

require __DIR__.'/settings.php';
