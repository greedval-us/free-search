<?php

use App\Http\Controllers\Bluesky\BlueskySearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('bluesky', 'Bluesky')
    ->middleware('feature.access')
    ->name('bluesky');

Route::prefix('bluesky')->name('bluesky.')->group(function (): void {
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
});
