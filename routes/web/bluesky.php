<?php

use App\Http\Controllers\Bluesky\BlueskyAnalyticsController;
use App\Http\Controllers\Bluesky\BlueskySearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('bluesky', 'Bluesky')
    ->middleware('feature.access')
    ->name('bluesky');

Route::prefix('bluesky')->name('bluesky.')->group(function (): void {
    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('summary', [BlueskyAnalyticsController::class, 'summary'])
            ->middleware(['feature.access', 'throttle:30,1'])
            ->name('summary');

        Route::get('report', [BlueskyAnalyticsController::class, 'report'])
            ->middleware(['feature.access', 'throttle:20,1'])
            ->name('report');
    });

    Route::prefix('search')->name('search.')->group(function (): void {
        Route::get('', [BlueskySearchController::class, 'search'])
            ->middleware('throttle:45,1')
            ->name('index');
    });

    Route::prefix('posts')->name('posts.')->group(function (): void {
        Route::get('likes', [BlueskySearchController::class, 'likes'])
            ->middleware('throttle:60,1')
            ->name('likes');

        Route::get('reposts', [BlueskySearchController::class, 'reposts'])
            ->middleware('throttle:60,1')
            ->name('reposts');

        Route::get('thread', [BlueskySearchController::class, 'thread'])
            ->middleware('throttle:60,1')
            ->name('thread');
    });

    Route::prefix('actors')->name('actors.')->group(function (): void {
        Route::get('feed', [BlueskySearchController::class, 'authorFeed'])
            ->middleware('throttle:60,1')
            ->name('feed');

        Route::get('followers', [BlueskySearchController::class, 'followers'])
            ->middleware('throttle:60,1')
            ->name('followers');

        Route::get('follows', [BlueskySearchController::class, 'follows'])
            ->middleware('throttle:60,1')
            ->name('follows');
    });
});
