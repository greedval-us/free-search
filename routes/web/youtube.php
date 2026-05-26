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
            ->middleware(['feature.access', 'throttle:20,1'])
            ->name('summary');
        Route::get('report', [YouTubeAnalyticsController::class, 'report'])
            ->middleware(['feature.access', 'throttle:10,1'])
            ->name('report');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::get('comments', [YouTubeParserController::class, 'comments'])
            ->middleware(['feature.access', 'throttle:30,1'])
            ->name('comments');
        Route::post('start', [YouTubeParserController::class, 'start'])
            ->middleware(['feature.access', 'throttle:10,1'])
            ->name('start');
        Route::get('status/{runId}', [YouTubeParserController::class, 'status'])
            ->middleware(['feature.access', 'throttle:40,1'])
            ->name('status');
        Route::post('stop/{runId}', [YouTubeParserController::class, 'stop'])
            ->middleware(['feature.access', 'throttle:20,1'])
            ->name('stop');
        Route::get('download-excel/{runId}', [YouTubeParserController::class, 'downloadExcel'])
            ->middleware(['feature.access', 'throttle:10,1'])
            ->name('download-excel');
        Route::get('download-json/{runId}', [YouTubeParserController::class, 'downloadJson'])
            ->middleware(['feature.access', 'throttle:10,1'])
            ->name('download-json');
    });
});
