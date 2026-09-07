<?php

use App\Http\Controllers\YouTube\YouTubeAnalyticsController;
use App\Http\Controllers\YouTube\YouTubeParserController;
use App\Http\Controllers\YouTube\YouTubeSearchController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('youtube', 'YouTube')
    ->middleware('feature.access')
    ->name('youtube');

Route::prefix('youtube')->name('youtube.')->group(function (): void {
    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('videos', [YouTubeSearchController::class, 'videos'])
            ->middleware(RouteThrottle::STANDARD_SEARCH)
            ->name('videos');
        Route::get('comments-preview', [YouTubeParserController::class, 'commentsPreview'])
            ->middleware(['feature.access', RouteThrottle::STANDARD_SEARCH])
            ->name('comments-preview');
    });

    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [YouTubeAnalyticsController::class, 'summary'])
            ->middleware(['feature.access', RouteThrottle::ANALYTICS_SUMMARY])
            ->name('summary');
        Route::get('report', [YouTubeAnalyticsController::class, 'report'])
            ->middleware(['feature.access', RouteThrottle::ANALYTICS_REPORT])
            ->name('report');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::get('comments', [YouTubeParserController::class, 'comments'])
            ->middleware(['feature.access', RouteThrottle::STANDARD_SEARCH])
            ->name('comments');
        Route::post('start', [YouTubeParserController::class, 'start'])
            ->middleware(['feature.access', RouteThrottle::PARSER_START])
            ->name('start');
        Route::get('status/{runId}', [YouTubeParserController::class, 'status'])
            ->middleware(['feature.access', RouteThrottle::PARSER_STATUS])
            ->name('status');
        Route::post('stop/{runId}', [YouTubeParserController::class, 'stop'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('stop');
        Route::get('history', [YouTubeParserController::class, 'history'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('history');
        Route::get('download-excel/{runId}', [YouTubeParserController::class, 'downloadExcel'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-excel');
        Route::get('download-json/{runId}', [YouTubeParserController::class, 'downloadJson'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-json');
    });
});
