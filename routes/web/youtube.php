<?php

use App\Http\Controllers\YouTube\YouTubeAnalyticsController;
use App\Http\Controllers\YouTube\YouTubeParserController;
use App\Http\Controllers\YouTube\YouTubeSearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('youtube', 'YouTube')->name('youtube');

Route::prefix('youtube')->name('youtube.')->group(function (): void {
    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('videos', [YouTubeSearchController::class, 'videos'])
            ->middleware('throttle:30,1')
            ->name('videos');
    });

    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [YouTubeAnalyticsController::class, 'summary'])
            ->middleware('throttle:20,1')
            ->name('summary');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::get('comments', [YouTubeParserController::class, 'comments'])
            ->middleware('throttle:30,1')
            ->name('comments');
    });
});
