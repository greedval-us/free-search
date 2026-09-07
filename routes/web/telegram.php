<?php

use App\Http\Controllers\Telegram\TelegramAnalyticsController;
use App\Http\Controllers\Telegram\TelegramParserController;
use App\Http\Controllers\Telegram\TelegramSearchController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('telegram', 'Telegram')
    ->middleware('feature.access')
    ->name('telegram');

Route::prefix('telegram')->name('telegram.')->group(function (): void {
    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('messages', [TelegramSearchController::class, 'messages'])
            ->middleware(RouteThrottle::TELEGRAM_SEARCH)
            ->name('messages');
        Route::get('comments', [TelegramSearchController::class, 'comments'])
            ->middleware(RouteThrottle::TELEGRAM_SEARCH)
            ->name('comments');
    });

    Route::get('media/{chatUsername}/{messageId}', [TelegramSearchController::class, 'media'])
        ->middleware(RouteThrottle::TELEGRAM_MEDIA)
        ->name('media');

    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [TelegramAnalyticsController::class, 'summary'])
            ->middleware(['feature.access', RouteThrottle::ANALYTICS_SUMMARY])
            ->name('summary');
        Route::get('report', [TelegramAnalyticsController::class, 'report'])
            ->middleware(['feature.access', RouteThrottle::ANALYTICS_REPORT])
            ->name('report');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::post('start', [TelegramParserController::class, 'start'])
            ->middleware(['feature.access', RouteThrottle::PARSER_START])
            ->name('start');
        Route::get('status/{runId}', [TelegramParserController::class, 'status'])
            ->middleware(['feature.access', RouteThrottle::PARSER_STATUS])
            ->name('status');
        Route::get('history', [TelegramParserController::class, 'history'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('history');
        Route::post('stop/{runId}', [TelegramParserController::class, 'stop'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('stop');
        Route::get('download-excel/{runId}', [TelegramParserController::class, 'downloadExcel'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-excel');
        Route::get('download-json/{runId}', [TelegramParserController::class, 'downloadJson'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-json');
    });
});
