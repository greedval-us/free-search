<?php

use App\Http\Controllers\Bluesky\BlueskyAnalyticsController;
use App\Http\Controllers\Bluesky\BlueskyParserController;
use App\Http\Controllers\Bluesky\BlueskySearchController;
use App\Support\Http\RouteThrottle;
use Illuminate\Support\Facades\Route;

Route::inertia('bluesky', 'Bluesky')
    ->middleware('feature.access')
    ->name('bluesky');

Route::prefix('bluesky')->name('bluesky.')->group(function (): void {
    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [BlueskyAnalyticsController::class, 'summary'])
            ->middleware(['feature.access', RouteThrottle::FEDIVERSE_ANALYTICS_SUMMARY])
            ->name('summary');

        Route::get('report', [BlueskyAnalyticsController::class, 'report'])
            ->middleware(['feature.access', RouteThrottle::FEDIVERSE_ANALYTICS_REPORT])
            ->name('report');
    });

    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('', [BlueskySearchController::class, 'search'])
            ->middleware(RouteThrottle::FEDIVERSE_SEARCH)
            ->name('index');
    });

    Route::prefix('parser')->name('parser.')->group(function (): void {
        Route::post('start', [BlueskyParserController::class, 'start'])
            ->middleware(['feature.access', RouteThrottle::PARSER_START])
            ->name('start');
        Route::get('status/{runId}', [BlueskyParserController::class, 'status'])
            ->middleware(['feature.access', RouteThrottle::PARSER_STATUS])
            ->name('status');
        Route::post('stop/{runId}', [BlueskyParserController::class, 'stop'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('stop');
        Route::get('history', [BlueskyParserController::class, 'history'])
            ->middleware(['feature.access', RouteThrottle::PARSER_CONTROL])
            ->name('history');
        Route::get('download-excel/{runId}', [BlueskyParserController::class, 'downloadExcel'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-excel');
        Route::get('download-json/{runId}', [BlueskyParserController::class, 'downloadJson'])
            ->middleware(['feature.access', RouteThrottle::PARSER_DOWNLOAD])
            ->name('download-json');
    });

    Route::prefix('posts')->name('posts.')->group(function (): void {
        Route::get('likes', [BlueskySearchController::class, 'likes'])
            ->middleware(RouteThrottle::DETAIL_LOOKUP)
            ->name('likes');

        Route::get('reposts', [BlueskySearchController::class, 'reposts'])
            ->middleware(RouteThrottle::DETAIL_LOOKUP)
            ->name('reposts');

        Route::get('thread', [BlueskySearchController::class, 'thread'])
            ->middleware(RouteThrottle::DETAIL_LOOKUP)
            ->name('thread');
    });

    Route::prefix('actors')->name('actors.')->group(function (): void {
        Route::get('feed', [BlueskySearchController::class, 'authorFeed'])
            ->middleware(RouteThrottle::DETAIL_LOOKUP)
            ->name('feed');

        Route::get('followers', [BlueskySearchController::class, 'followers'])
            ->middleware(RouteThrottle::DETAIL_LOOKUP)
            ->name('followers');

        Route::get('follows', [BlueskySearchController::class, 'follows'])
            ->middleware(RouteThrottle::DETAIL_LOOKUP)
            ->name('follows');
    });
});
