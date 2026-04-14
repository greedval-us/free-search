<?php

use App\Http\Controllers\Telegram\TelegramAnalyticsController;
use App\Http\Controllers\Telegram\TelegramParserController;
use App\Http\Controllers\Telegram\TelegramSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('telegram', 'Telegram')->name('telegram');

Route::prefix('telegram')->name('telegram.')->group(function (): void {
    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('messages', [TelegramSearchController::class, 'messages'])
            ->middleware('throttle:90,1')
            ->name('messages');
        Route::get('comments', [TelegramSearchController::class, 'comments'])
            ->middleware('throttle:90,1')
            ->name('comments');
    });

    Route::get('media/{chatUsername}/{messageId}', [TelegramSearchController::class, 'media'])
        ->middleware('throttle:120,1')
        ->name('media');

    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [TelegramAnalyticsController::class, 'summary'])
            ->middleware('throttle:20,1')
            ->name('summary');
        Route::get('report', [TelegramAnalyticsController::class, 'report'])
            ->middleware('throttle:10,1')
            ->name('report');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::post('start', [TelegramParserController::class, 'start'])
            ->middleware('throttle:10,1')
            ->name('start');
        Route::get('status/{runId}', [TelegramParserController::class, 'status'])
            ->middleware('throttle:40,1')
            ->name('status');
        Route::post('stop/{runId}', [TelegramParserController::class, 'stop'])
            ->middleware('throttle:20,1')
            ->name('stop');
        Route::get('download-excel/{runId}', [TelegramParserController::class, 'downloadExcel'])
            ->middleware('throttle:10,1')
            ->name('download-excel');
        Route::get('download-json/{runId}', [TelegramParserController::class, 'downloadJson'])
            ->middleware('throttle:10,1')
            ->name('download-json');
    });
});
